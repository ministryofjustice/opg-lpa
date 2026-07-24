<?php

namespace ApplicationTest\Model\Service\OneLogin;

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

        $logger = Mockery::spy(LoggerInterface::class);

        $this->service = new Service();
        $this->service->setLogger($logger);
        $this->service->setAuthorisationClientManager($this->clientManager);
        $this->service->setAuthorizationService($this->authorizationService);
        $this->service->setAuthenticationService($this->authenticationService);
        $this->service->setUserRepository($this->userRepository);
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

    public function testHandleCallbackLinkedReturnsIdentity(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:sub-abc123';
        $email = 'alice@example.com';

        $tokenSet = $this->makeTokenSet($sub, $email);

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($tokenSet);

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

        $result = $this->service->handleCallback('auth-code', 'state-abc', 'nonce-xyz', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertSame($sub, $result['sub']);
        $this->assertSame($email, $result['email']);
        $this->assertSame('user-1', $result['identity']['userId']);
        $this->assertSame('tok-xyz', $result['identity']['token']);
        $this->assertSame($expires->format('c'), $result['identity']['tokenExpiresAt']);
    }

    public function testHandleCallbackLinkedResetsFailedCounterWhenNonZero(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $tokenSet = $this->makeTokenSet($sub, null);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

        $user = $this->makeUser('user-2', 3, new DateTime('2026-01-01'));

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-2');
        $this->userRepository->shouldReceive('resetFailedLoginCounter')->once()->with('user-2');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $result = $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
    }

    public function testHandleCallbackUnlinkedReturnsFalseLinked(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:new-sub';
        $email = 'new@example.com';

        $tokenSet = $this->makeTokenSet($sub, $email);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

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
        $tokenSet = $this->makeTokenSet($sub, null);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->with($sub)->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', self::REDIRECT_URI);

        $this->assertSame($sub, $result['sub']);
    }

    private function makeTokenSet(string $sub, ?string $email): MockInterface|TokenSetInterface
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn('header.payload.sig');

        $claims = ['sub' => $sub];
        if ($email !== null) {
            $claims['email'] = $email;
        }

        $tokenSet->shouldReceive('claims')->andReturn($claims);

        return $tokenSet;
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
