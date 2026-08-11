<?php

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\AuthorizationServiceInterface;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service;
use DateTime;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Token\TokenSetInterface;
use MakeShared\OneLogin\LinkReason;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ServiceTest extends MockeryTestCase
{
    private Service $service;
    private MockInterface|AuthorisationClientManager $clientManager;
    private MockInterface|AuthorizationServiceInterface $authorizationService;
    private MockInterface|AuthenticationService $authenticationService;
    private MockInterface|UserRepositoryInterface $userRepository;
    private MockInterface|LogRepositoryInterface $logRepository;
    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;
    private MockInterface|ClientInterface $oidcClient;

    private const REDIRECT_URI = 'https://front.example.com/auth/redirect';

    public function setUp(): void
    {
        $this->oidcClient = Mockery::mock(ClientInterface::class);

        $this->clientManager = Mockery::mock(AuthorisationClientManager::class);
        $this->clientManager->shouldReceive('get')
            ->andReturn($this->oidcClient)
            ->byDefault();

        $this->authorizationService  = Mockery::mock(AuthorizationServiceInterface::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->logRepository = Mockery::mock(LogRepositoryInterface::class);

        $this->sharedSpaceRepository = Mockery::mock(SharedSpaceRepositoryInterface::class);

        $logger = Mockery::spy(LoggerInterface::class);

        $this->service = new Service();
        $this->service->setLogger($logger);
        $this->service->setAuthorisationClientManager($this->clientManager);
        $this->service->setAuthorizationService($this->authorizationService);
        $this->service->setAuthenticationService($this->authenticationService);
        $this->service->setUserRepository($this->userRepository);
        $this->service->setLogRepository($this->logRepository);
        $this->service->setSharedSpaceRepository($this->sharedSpaceRepository);
    }

    public function testCreateAuthenticationRequestReturnsExpectedParams(): void
    {
        $seededBytes = $this->seedRandomBytes();
        $this->service->setRandomByteGenerator($seededBytes);

        $expectedState = bin2hex(str_repeat(chr(3), 12));
        $expectedNonce = bin2hex(str_repeat(chr(6), 16));
        $builtUrl      = 'https://oidc.example.com/auth?state=' . $expectedState;

        $this->authorizationService->shouldReceive('getAuthorizationUri')
            ->once()
            ->with($this->oidcClient, Mockery::on(function (array $params) use ($expectedState, $expectedNonce): bool {
                return $params['state'] === $expectedState
                    && $params['nonce'] === $expectedNonce
                    && $params['scope'] === 'openid email'
                    && $params['vtr'] === '["Cl.Cm"]'
                    && $params['redirect_uri'] === self::REDIRECT_URI;
            }))
            ->andReturn($builtUrl);

        $result = $this->service->createAuthenticationRequest(self::REDIRECT_URI);

        $this->assertSame($expectedState, $result['state']);
        $this->assertSame($expectedNonce, $result['nonce']);
        $this->assertSame($builtUrl, $result['url']);
    }

    public function testTwoCallsProduceDifferentStateAndNonce(): void
    {
        $this->authorizationService->shouldReceive('getAuthorizationUri')
            ->twice()
            ->andReturn('https://oidc.example.com/auth');

        $first  = $this->service->createAuthenticationRequest(self::REDIRECT_URI);
        $second = $this->service->createAuthenticationRequest(self::REDIRECT_URI);

        $this->assertNotSame($first['state'], $second['state']);
        $this->assertNotSame($first['nonce'], $second['nonce']);
    }

    public function testMissingClientManagerThrows(): void
    {
        $service = new Service();
        $service->setLogger(Mockery::spy(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuthorisationClientManager');

        $service->createAuthenticationRequest(self::REDIRECT_URI);
    }

    public function testMissingAuthorizationServiceThrows(): void
    {
        $service = new Service();
        $service->setLogger(Mockery::spy(LoggerInterface::class));
        $service->setAuthorisationClientManager($this->clientManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuthorizationService');

        $service->createAuthenticationRequest(self::REDIRECT_URI);
    }

    public function testMissingSharedSpaceRepositoryThrows(): void
    {
        $service = new Service();
        $service->setLogger(Mockery::spy(LoggerInterface::class));
        $service->setAuthorisationClientManager($this->clientManager);
        $service->setAuthorizationService($this->authorizationService);
        $service->setAuthenticationService($this->authenticationService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SharedSpaceRepository');

        $service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);
    }

    public function testHandleCallbackLinkedReturnsIdentity(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:sub-abc123';
        $email = 'alice@example.com';

        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($tokenSet);

        $this->stubUserInfo($sub, $email);

        $user = $this->makeUser('user-1', 0, new DateTime('2026-01-01 12:00:00'));

        $this->userRepository->shouldReceive('getByOneLoginSub')
            ->once()
            ->with($sub)
            ->andReturn($user);

        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-1');
        $this->userRepository->shouldNotReceive('resetFailedLoginCounter');

        $expires = new DateTime('+4500 seconds');
        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->with($user)
            ->andReturn(['token' => 'tok-xyz', 'expiresIn' => 4500, 'expiresAt' => $expires]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->once()
            ->with('user-1')
            ->andReturn('shared-space-9');

        $result = $this->service->handleCallback('auth-code', 'state-abc', 'nonce-xyz', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertSame($sub, $result['sub']);
        $this->assertSame($email, $result['email']);
        $this->assertSame('user-1', $result['identity']['userId']);
        $this->assertSame('tok-xyz', $result['identity']['token']);
        $this->assertSame($expires->format('c'), $result['identity']['tokenExpiresAt']);
        $this->assertSame('shared-space-9', $result['identity']['sharedSpaceId']);
    }

    public function testHandleCallbackLinkedResetsFailedCounterWhenNonZero(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->stubUserInfo($sub, 'reset@example.com');

        $user = $this->makeUser('user-2', 3, new DateTime('2026-01-01'));

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-2');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->once()
            ->with('user-2')
            ->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertNull($result['identity']['sharedSpaceId']);
    }

    public function testHandleCallbackUnlinkedReturnsFalseLinked(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:new-sub';
        $email = 'new@example.com';

        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->stubUserInfo($sub, $email);

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->with($sub)->andReturn(null);

        $this->authenticationService->shouldNotReceive('issueAuthToken');

        $result = $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);

        $this->assertFalse($result['linked']);
        $this->assertSame($sub, $result['sub']);
        $this->assertSame($email, $result['email']);
        $this->assertNull($result['identity']);
    }

    public function testHandleCallbackTokenExchangeFailureThrowsDomainException(): void
    {
        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andThrow(new \RuntimeException('provider error'));

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);
    }

    public function testHandleCallbackMissingIdTokenThrows(): void
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn(null);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);
    }

    public function testHandleCallbackMissingSubThrows(): void
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn('some.jwt.token');
        $tokenSet->shouldReceive('claims')->andReturn(['email' => 'x@example.com']);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);
    }

    public function testHandleCallbackSubReceived(): void
    {
        $sub      = 'urn:fdc:gov.uk:2022:sub';
        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->stubUserInfo($sub, 'sub@example.com');
        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->with($sub)->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);

        $this->assertSame($sub, $result['sub']);
    }

    public function testHandleCallbackMissingEmailThrows(): void
    {
        $sub      = 'urn:fdc:gov.uk:2022:sub';
        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        // UserInfo returns no email -> authentication failure.
        $this->stubUserInfo($sub, null);
        $this->userRepository->shouldNotReceive('getByOneLoginSub');

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);
    }

    public function testLinkExistingAccountSuccessSetsSubAndReturnsIdentity(): void
    {
        $sub  = 'urn:fdc:gov.uk:2022:new-sub';
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')
            ->once()->with('alice@example.com')->andReturn($user);

        $expires   = new DateTime('+4500 seconds');
        $lastLogin = new DateTime('2025-01-01 09:00:00');

        $this->authenticationService->shouldReceive('withPassword')
            ->once()
            ->with('alice@example.com', 'correct-horse', true)
            ->andReturn([
                'userId'        => 'user-1',
                'token'         => 'tok-xyz',
                'expiresIn'     => 4500,
                'expiresAt'     => $expires,
                'last_login'    => $lastLogin,
                'sharedSpaceId' => 'shared-space-9',
            ]);

        $this->userRepository->shouldReceive('setOneLoginSub')
            ->once()->with('user-1', $sub);

        $result = $this->service->linkExistingAccount('alice@example.com', 'correct-horse', $sub);

        $this->assertTrue($result['linked']);
        $identity = $result['identity'] ?? null;
        $this->assertIsArray($identity);
        $this->assertSame('user-1', $identity['userId']);
        $this->assertSame('tok-xyz', $identity['token']);
        $this->assertSame($expires->format('c'), $identity['tokenExpiresAt']);
        $this->assertSame($lastLogin->format('c'), $identity['lastLogin']);
        $this->assertSame('shared-space-9', $identity['sharedSpaceId']);
    }

    public function testLinkExistingAccountReturnsAccountNotFoundWhenNoUserAndNoDeletionLog(): void
    {
        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn(null);
        $this->logRepository->shouldReceive('getLogByIdentityHash')->once()->andReturn(null);
        $this->authenticationService->shouldNotReceive('withPassword');

        $result = $this->service->linkExistingAccount('gone@example.com', 'pw', 'urn:x');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_NOT_FOUND, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountReturnsAccountDeletedWhenDeletionLogExists(): void
    {
        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn(null);
        $this->logRepository->shouldReceive('getLogByIdentityHash')
            ->once()->andReturn(['type' => 'account-deleted', 'reason' => 'user-initiated']);
        $this->authenticationService->shouldNotReceive('withPassword');

        $result = $this->service->linkExistingAccount('deleted@example.com', 'pw', 'urn:x');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_DELETED, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountReturnsAlreadyLinkedWhenSubDiffers(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: 'urn:fdc:gov.uk:2022:someone-else');

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        // Must not attempt a password check or overwrite the existing link.
        $this->authenticationService->shouldNotReceive('withPassword');
        $this->userRepository->shouldNotReceive('setOneLoginSub');

        $result = $this->service->linkExistingAccount('taken@example.com', 'pw', 'urn:fdc:gov.uk:2022:me');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ALREADY_LINKED, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountMapsInvalidCredentials(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        $this->authenticationService->shouldReceive('withPassword')
            ->once()->andReturn('invalid-user-credentials');
        $this->userRepository->shouldNotReceive('setOneLoginSub');

        $result = $this->service->linkExistingAccount('alice@example.com', 'wrong', 'urn:x');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::INVALID_CREDENTIALS, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountMapsLockedAccount(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        $this->authenticationService->shouldReceive('withPassword')
            ->once()->andReturn('account-locked/max-login-attempts');

        $result = $this->service->linkExistingAccount('alice@example.com', 'pw', 'urn:x');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_LOCKED, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountMapsInactiveAccount(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        $this->authenticationService->shouldReceive('withPassword')
            ->once()->andReturn('account-not-active');

        $result = $this->service->linkExistingAccount('alice@example.com', 'pw', 'urn:x');

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_NOT_ACTIVE, $result['reason'] ?? null);
    }

    public function testCreateAndLinkAccountCreatesActivePasswordlessUserAndReturnsIdentity(): void
    {
        $this->service->setRandomByteGenerator(fn(int $n): string => str_repeat("\x00", $n));
        $userId = str_repeat('0', 32);
        $sub    = 'urn:fdc:gov.uk:2022:brand-new';

        $this->userRepository->shouldReceive('create')
            ->once()
            ->with($userId, Mockery::on(function (array $details): bool {
                return $details['identity'] === null
                    && $details['password_hash'] === null
                    && $details['activation_token'] === null
                    && $details['active'] === true
                    && $details['failed_login_attempts'] === 0;
            }))
            ->andReturn(true);

        $this->userRepository->shouldReceive('setOneLoginSub')->once()->with($userId, $sub);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with($userId);

        $user = Mockery::mock(UserInterface::class);
        $this->userRepository->shouldReceive('getById')->once()->with($userId)->andReturn($user);

        $expires = new DateTime('+4500 seconds');
        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->with($user)
            ->andReturn(['token' => 'tok-new', 'expiresIn' => 4500, 'expiresAt' => $expires]);

        $result = $this->service->createAndLinkAccount($sub);

        $this->assertSame($userId, $result['userId']);
        $this->assertSame('tok-new', $result['token']);
        $this->assertSame($expires->format('c'), $result['tokenExpiresAt']);
        $this->assertNull($result['sharedSpaceId']);
        $this->assertNotEmpty($result['lastLogin']);
    }

    private function makeLinkUser(?string $oneLoginSub): MockInterface|UserInterface
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('oneLoginSub')->andReturn($oneLoginSub);

        return $user;
    }

    private function makeTokenSet(string $sub): MockInterface|TokenSetInterface
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn('header.payload.sig');
        $tokenSet->shouldReceive('claims')->andReturn(['sub' => $sub]);

        return $tokenSet;
    }

    private function stubUserInfo(string $sub, ?string $email): void
    {
        $userInfo = ['sub' => $sub];
        if ($email !== null) {
            $userInfo['email'] = $email;
        }

        $this->authorizationService->shouldReceive('getUserInfo')
            ->once()
            ->andReturn($userInfo);
    }

    private function makeUser(string $id, int $failedAttempts, DateTime $lastLogin): MockInterface|UserInterface
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn($id);
        $user->shouldReceive('failedLoginAttempts')->andReturn($failedAttempts);
        $user->shouldReceive('lastLoginAt')->andReturn($lastLogin);

        return $user;
    }

    /**
     * Returns a callable seam that produces deterministic bytes for testing.
     *
     * @return callable(int): string
     */
    private function seedRandomBytes(): callable
    {
        $call = 0;

        return static function (int $length) use (&$call): string {
            $call++;
            return str_repeat(chr($call * 3), $length);
        };
    }
}
