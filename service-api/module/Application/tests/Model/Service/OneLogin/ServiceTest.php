<?php

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\AuthorizationServiceInterface;
use Application\Model\Service\OneLogin\KeyPairManager;
use Application\Model\Service\OneLogin\LogoutTokenException;
use Application\Model\Service\OneLogin\LogoutTokenVerifier;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service;
use DateTime;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadata;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\TokenSetInterface;
use InvalidArgumentException;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use MakeShared\DataModel\User\User as ProfileUserModel;
use MakeShared\OneLogin\LinkReason;
use MakeShared\OneLogin\UserType;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class ServiceTest extends MockeryTestCase
{
    private const array PROFILE = [
        'name'    => ['title' => 'Mr', 'first' => 'Joe', 'last' => 'Bloggs'],
        'dob'     => ['date' => '1980-04-14T00:00:00.000Z'],
        'address' => ['address1' => '1 High Street', 'address2' => 'Town', 'postcode' => 'AB1 2CD'],
    ];

    private Service $service;
    private MockInterface|AuthorisationClientManager $clientManager;
    private MockInterface|AuthorizationServiceInterface $authorizationService;
    private MockInterface|AuthenticationService $authenticationService;
    private MockInterface|UserRepositoryInterface $userRepository;
    private MockInterface|LogRepositoryInterface $logRepository;
    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;
    private MockInterface|ClientInterface $oidcClient;
    private MockInterface|LoggerInterface $logger;
    private MockInterface|LogoutTokenVerifier $logoutTokenVerifier;
    private MockInterface|CacheInterface $cache;
    private KeyPairManager $keyPairManager;

    private const REDIRECT_URI = 'https://front.example.com/auth/redirect';

    private const string CLIENT_ID              = 'test-client-id';
    private const string KEY_ID                 = 'test-kid';
    private const string AUTHORIZATION_ENDPOINT = 'https://oidc.integration.account.gov.uk/authorize';

    private const int SIGNED_IN_AT  = 1791280000;
    private const int SIGNED_OUT_AT = 1791283600;

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

        $this->logger = Mockery::spy(LoggerInterface::class);

        $this->logoutTokenVerifier = Mockery::mock(LogoutTokenVerifier::class);

        $this->cache = Mockery::mock(CacheInterface::class);

        $this->keyPairManager = new KeyPairManager(KeyPairManagerTest::rsaPrivateKey(), self::KEY_ID);

        $this->service = new Service();
        $this->service->setLogger($this->logger);
        $this->service->setLogoutTokenVerifier($this->logoutTokenVerifier);
        $this->service->setCache($this->cache);
        $this->service->setAuthorisationClientManager($this->clientManager);
        $this->service->setAuthorizationService($this->authorizationService);
        $this->service->setAuthenticationService($this->authenticationService);
        $this->service->setUserRepository($this->userRepository);
        $this->service->setLogRepository($this->logRepository);
        $this->service->setSharedSpaceRepository($this->sharedSpaceRepository);
        $this->service->setKeyPairManager($this->keyPairManager);
    }

    public function testCreateAuthenticationRequestSignsTheParametersIntoARequestObject(): void
    {
        $this->stubClientForRequestObject();
        $this->service->setRandomByteGenerator($this->seedRandomBytes());

        $expectedState     = bin2hex(str_repeat(chr(3), 12));
        $expectedNonce     = bin2hex(str_repeat(chr(6), 16));
        $expectedVerifier  = rtrim(strtr(base64_encode(str_repeat(chr(9), 32)), '+/', '-_'), '=');
        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $expectedVerifier, true)), '+/', '-_'), '=');

        // Only the request object travels as a parameter; facile adds response_type and client_id.
        $requestObject = null;
        $this->authorizationService->shouldReceive('getAuthorizationUri')
            ->once()
            ->with($this->oidcClient, Mockery::on(function (array $parameters) use (&$requestObject): bool {
                $requestObject = $parameters['request'] ?? null;

                return array_keys($parameters) === ['scope', 'request'] && $parameters['scope'] === 'openid email';
            }))
            ->andReturn('https://oidc.example.com/authorize?request=signed.request.object');

        $result = $this->service->createAuthenticationRequest(self::REDIRECT_URI);

        $this->assertSame($expectedState, $result['state']);
        $this->assertSame($expectedNonce, $result['nonce']);
        $this->assertSame($expectedVerifier, $result['codeVerifier']);
        $this->assertSame('https://oidc.example.com/authorize?request=signed.request.object', $result['url']);

        $jws = (new CompactSerializer())->unserialize($requestObject);

        $this->assertSame(['alg' => 'RS256', 'kid' => self::KEY_ID], $jws->getSignature(0)->getProtectedHeader());
        $this->assertTrue(
            (new JWSVerifier(new AlgorithmManager([new RS256()])))
                ->verifyWithKey($jws, $this->keyPairManager->jwk()->toPublic(), 0),
        );

        $claims = json_decode((string) $jws->getPayload(), true, 512, JSON_THROW_ON_ERROR);
        $expiresAt = $claims['exp'];
        unset($claims['exp']);

        $this->assertSame([
            'aud'                   => self::AUTHORIZATION_ENDPOINT,
            'iss'                   => self::CLIENT_ID,
            'client_id'             => self::CLIENT_ID,
            'response_type'         => 'code',
            'scope'                 => 'openid email',
            'redirect_uri'          => self::REDIRECT_URI,
            'state'                 => $expectedState,
            'nonce'                 => $expectedNonce,
            'vtr'                   => ['Cl.Cm'],
            'code_challenge'        => $expectedChallenge,
            'code_challenge_method' => 'S256',
        ], $claims);
        $this->assertEqualsWithDelta(time() + 300, $expiresAt, 5);
    }

    public function testCodeVerifierMeetsThePkceLengthRules(): void
    {
        $this->stubClientForRequestObject();
        $this->authorizationService->shouldReceive('getAuthorizationUri')->andReturn('https://oidc.example.com/authorize');

        $verifier = $this->service->createAuthenticationRequest(self::REDIRECT_URI)['codeVerifier'];

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-._~]{43,128}$/', $verifier);
    }

    public function testTwoCallsProduceDifferentStateNonceAndCodeVerifier(): void
    {
        $this->stubClientForRequestObject();
        $this->authorizationService->shouldReceive('getAuthorizationUri')
            ->twice()
            ->andReturn('https://oidc.example.com/authorize');

        $first  = $this->service->createAuthenticationRequest(self::REDIRECT_URI);
        $second = $this->service->createAuthenticationRequest(self::REDIRECT_URI);

        $this->assertNotSame($first['state'], $second['state']);
        $this->assertNotSame($first['nonce'], $second['nonce']);
        $this->assertNotSame($first['codeVerifier'], $second['codeVerifier']);
    }

    public function testMissingKeyPairManagerThrows(): void
    {
        $service = new Service();
        $service->setLogger(Mockery::spy(LoggerInterface::class));
        $service->setAuthorisationClientManager($this->clientManager);
        $service->setAuthorizationService($this->authorizationService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('KeyPairManager must be set');

        $service->createAuthenticationRequest(self::REDIRECT_URI);
    }

    public function testPublicJwksComesFromTheKeyPairManager(): void
    {
        $jwks = $this->service->publicJwks();

        $this->assertCount(1, $jwks['keys']);
        $this->assertSame(self::KEY_ID, $jwks['keys'][0]['kid']);
        $this->assertArrayNotHasKey('d', $jwks['keys'][0]);
    }

    public function testCreateLogoutRequestBuildsEndSessionUrl(): void
    {
        $this->stubEndSessionEndpoint('https://oidc.example.com/logout');

        $url = $this->service->createLogoutRequest('header.payload.sig', 'https://www.gov.uk/done/lasting-power-of-attorney');

        $this->assertSame(
            'https://oidc.example.com/logout'
                . '?id_token_hint=header.payload.sig'
                . '&post_logout_redirect_uri=https%3A%2F%2Fwww.gov.uk%2Fdone%2Flasting-power-of-attorney',
            $url,
        );
    }

    public function testCreateLogoutRequestThrowsWhenDiscoveryHasNoEndSessionEndpoint(): void
    {
        $this->stubEndSessionEndpoint(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('end_session_endpoint');

        $this->service->createLogoutRequest('header.payload.sig', 'https://www.gov.uk/done/lasting-power-of-attorney');
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

        $service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
    }

    public function testHandleCallbackLinkedReturnsIdentity(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:sub-abc123';
        $email = 'alice@example.com';

        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->with(
                $this->oidcClient,
                ['code' => 'auth-code', 'state' => 'state-abc'],
                self::REDIRECT_URI,
                Mockery::on(fn (AuthSessionInterface $session): bool => $session->getState() === 'state-abc'
                    && $session->getNonce() === 'nonce-xyz'
                    && $session->getCodeVerifier() === 'verifier-123'),
            )
            ->andReturn($tokenSet);

        $this->stubUserInfo($sub, $email);

        $user = $this->makeUser('user-1', 0, new DateTime('2026-01-01 12:00:00'), $email);

        $this->userRepository->shouldReceive('getByOneLoginSub')
            ->once()
            ->with($sub)
            ->andReturn($user);

        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-1');
        $this->userRepository->shouldNotReceive('resetFailedLoginCounter');
        // Email is unchanged, so it must not be rewritten.
        $this->userRepository->shouldNotReceive('setOneLoginEmail');

        $expires = new DateTime('+4500 seconds');
        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->with($user)
            ->andReturn(['token' => 'tok-xyz', 'expiresIn' => 4500, 'expiresAt' => $expires]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->once()
            ->with('user-1')
            ->andReturn('shared-space-9');

        $result = $this->service->handleCallback('auth-code', 'state-abc', 'nonce-xyz', 'verifier-123', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertSame($sub, $result['sub']);
        $this->assertSame($email, $result['email']);
        $this->assertSame('header.payload.sig', $result['idToken']);
        $this->assertSame(self::SIGNED_IN_AT, $result['signedInAt']);
        $this->assertSame('user-1', $result['identity']['userId']);
        $this->assertSame('tok-xyz', $result['identity']['token']);
        $this->assertSame($expires->format('c'), $result['identity']['tokenExpiresAt']);
        $this->assertSame('shared-space-9', $result['identity']['sharedSpaceId']);
    }

    public function testHandleCallbackRefreshesEmailWhenChangedAtOneLogin(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, 'new.address@example.com');

        $user = $this->makeUser('user-3', 0, new DateTime('2026-01-01'), 'old.address@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-3');

        $this->userRepository->shouldReceive('setOneLoginEmail')
            ->once()
            ->with('user-3', 'new.address@example.com');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertSame('new.address@example.com', $result['email']);
    }

    public function testHandleCallbackDoesNotWriteWhenEmailUnchanged(): void
    {
        $sub   = 'urn:fdc:gov.uk:2022:sub-abc123';
        $email = 'steady@example.com';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, $email);

        $user = $this->makeUser('user-4', 0, new DateTime('2026-01-01'), $email);

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-4');
        $this->userRepository->shouldNotReceive('setOneLoginEmail');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
    }

    public function testHandleCallbackDoesNotWriteWhenEmailDiffersOnlyByCaseOrWhitespace(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, '  Steady@Example.COM  ');

        $user = $this->makeUser('user-5', 0, new DateTime('2026-01-01'), 'steady@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-5');
        $this->userRepository->shouldNotReceive('setOneLoginEmail');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
    }

    public function testHandleCallbackStoresEmailWhenNoneHeld(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, 'backfilled@example.com');

        $user = $this->makeUser('user-6', 0, new DateTime('2026-01-01'), null);

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-6');

        $this->userRepository->shouldReceive('setOneLoginEmail')
            ->once()
            ->with('user-6', 'backfilled@example.com');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
    }

    public function testHandleCallbackUnlinkedDoesNotRefreshEmail(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-unknown';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, 'stranger@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->with($sub)->andReturn(null);
        $this->userRepository->shouldNotReceive('setOneLoginEmail');
        $this->userRepository->shouldNotReceive('updateLastLoginTime');

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertFalse($result['linked']);
        $this->assertSame('stranger@example.com', $result['email']);
    }

    public function testHandleCallbackNormalisesIncomingEmailBeforeStoringIt(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, "  padded@example.com\n");

        $user = $this->makeUser('user-7', 0, new DateTime('2026-01-01'), 'old@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-7');

        $this->userRepository->shouldReceive('setOneLoginEmail')
            ->once()
            ->with('user-7', 'padded@example.com');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertSame('padded@example.com', $result['email']);
    }

    public function testHandleCallbackRejectsWhitespaceOnlyEmailClaim(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, '   ');

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
    }

    public function testHandleCallbackStillSignsUserInWhenEmailRefreshFails(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet($sub));

        $this->stubUserInfo($sub, 'new@example.com');

        $user = $this->makeUser('user-8', 0, new DateTime('2026-01-01'), 'old@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-8');

        $this->userRepository->shouldReceive('setOneLoginEmail')
            ->once()
            ->andThrow(new RuntimeException('database is on fire'));

        $this->logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $extra) {
                $this->assertSame('auth.onelogin.email_refresh_failed', $message);
                $this->assertSame('user-8', $extra['user_id']);

                return true;
            });

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->once()->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertTrue($result['linked']);
        $this->assertSame('tok', $result['identity']['token']);
    }

    public function testHandleCallbackLinkedResetsFailedCounterWhenNonZero(): void
    {
        $sub = 'urn:fdc:gov.uk:2022:sub-abc123';

        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->stubUserInfo($sub, 'reset@example.com');

        $user = $this->makeUser('user-2', 3, new DateTime('2026-01-01'), 'reset@example.com');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with('user-2');
        $this->userRepository->shouldNotReceive('setOneLoginEmail');

        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->andReturn(['token' => 'tok', 'expiresIn' => 4500, 'expiresAt' => new DateTime()]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->once()
            ->with('user-2')
            ->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

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

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

        $this->assertFalse($result['linked']);
        $this->assertSame($sub, $result['sub']);
        $this->assertSame($email, $result['email']);
        $this->assertSame('header.payload.sig', $result['idToken']);
        $this->assertNull($result['identity']);
    }

    public function testHandleCallbackTokenExchangeFailureThrowsDomainException(): void
    {
        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andThrow(new \RuntimeException('provider error'));

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
    }

    public function testHandleCallbackMissingIdTokenThrows(): void
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn(null);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
    }

    public function testHandleCallbackRejectsAnIdTokenWithoutIat(): void
    {
        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet('urn:fdc:gov.uk:2022:sub-abc', ['iat' => null]));
        $this->authorizationService->shouldNotReceive('getUserInfo');

        try {
            $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
            $this->fail('Expected the ID token to be rejected');
        } catch (OneLoginAuthenticationException $e) {
            $this->assertSame('missing_iat_claim', $e->reason());
        }
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function rejectedVotProvider(): array
    {
        return [
            'vot missing'                   => [null, 'missing_vot_claim'],
            'low authentication'            => ['Cl', 'invalid_vot_claim'],
            'identity level included'       => ['Cl.Cm.P2', 'invalid_vot_claim'],
            'vot as an array, not a string' => [['Cl.Cm'], 'invalid_vot_claim'],
            'empty vot'                     => ['', 'invalid_vot_claim'],
        ];
    }

    /**
     * @dataProvider rejectedVotProvider
     */
    public function testHandleCallbackRejectsAnIdTokenWithoutTheRequestedVot(mixed $vot, string $expectedReason): void
    {
        $this->authorizationService->shouldReceive('callback')
            ->once()
            ->andReturn($this->makeTokenSet('urn:fdc:gov.uk:2022:sub-abc', ['vot' => $vot]));

        $this->authorizationService->shouldNotReceive('getUserInfo');
        $this->userRepository->shouldNotReceive('getByOneLoginSub');
        $this->authenticationService->shouldNotReceive('issueAuthToken');

        try {
            $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
            $this->fail('Expected the ID token to be rejected');
        } catch (OneLoginAuthenticationException $e) {
            $this->assertSame($expectedReason, $e->reason());
        }
    }

    public function testHandleCallbackLogsTheVotItRejects(): void
    {
        $this->authorizationService->shouldReceive('callback')
            ->andReturn($this->makeTokenSet('urn:fdc:gov.uk:2022:sub-abc', ['vot' => 'Cl.Cm.P2']));

        $this->expectException(OneLoginAuthenticationException::class);

        try {
            $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
        } finally {
            $this->logger->shouldHaveReceived('warning')->with('auth.onelogin.unexpected_vot', ['vot' => 'Cl.Cm.P2'])->once();
        }
    }

    public function testHandleCallbackMissingSubThrows(): void
    {
        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn('some.jwt.token');
        $tokenSet->shouldReceive('claims')->andReturn(['email' => 'x@example.com']);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);

        $this->expectException(OneLoginAuthenticationException::class);

        $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
    }

    public function testHandleCallbackSubReceived(): void
    {
        $sub      = 'urn:fdc:gov.uk:2022:sub';
        $tokenSet = $this->makeTokenSet($sub);

        $this->authorizationService->shouldReceive('callback')->once()->andReturn($tokenSet);
        $this->stubUserInfo($sub, 'sub@example.com');
        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->with($sub)->andReturn(null);

        $result = $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);

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

        $this->service->handleCallback('code', 'state', 'nonce', 'verifier', self::REDIRECT_URI);
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
            ->once()->with('user-1', $sub, 'joe.bloggs@gmail.com', false);

        $result = $this->service->linkExistingAccount('alice@example.com', 'correct-horse', $sub, 'joe.bloggs@gmail.com', UserType::Lay);

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

        $result = $this->service->linkExistingAccount('gone@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com', UserType::Lay);

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_NOT_FOUND, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountReturnsAccountDeletedWhenDeletionLogExists(): void
    {
        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn(null);
        $this->logRepository->shouldReceive('getLogByIdentityHash')
            ->once()->andReturn(['type' => 'account-deleted', 'reason' => 'user-initiated']);
        $this->authenticationService->shouldNotReceive('withPassword');

        $result = $this->service->linkExistingAccount('deleted@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com', UserType::Lay);

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

        $result = $this->service->linkExistingAccount('taken@example.com', 'pw', 'urn:fdc:gov.uk:2022:me', 'joe.bloggs@gmail.com', UserType::Lay);

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

        $result = $this->service->linkExistingAccount('alice@example.com', 'wrong', 'urn:x', 'joe.bloggs@gmail.com', UserType::Lay);

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::INVALID_CREDENTIALS, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountMapsLockedAccount(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        $this->authenticationService->shouldReceive('withPassword')
            ->once()->andReturn('account-locked/max-login-attempts');

        $result = $this->service->linkExistingAccount('alice@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com', UserType::Lay);

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_LOCKED, $result['reason'] ?? null);
    }

    public function testLinkExistingAccountMapsInactiveAccount(): void
    {
        $user = $this->makeLinkUser(oneLoginSub: null);

        $this->userRepository->shouldReceive('getByUsername')->once()->andReturn($user);
        $this->authenticationService->shouldReceive('withPassword')
            ->once()->andReturn('account-not-active');

        $result = $this->service->linkExistingAccount('alice@example.com', 'pw', 'urn:x', 'joe.bloggs@gmail.com', UserType::Lay);

        $this->assertFalse($result['linked']);
        $this->assertSame(LinkReason::ACCOUNT_NOT_ACTIVE, $result['reason'] ?? null);
    }

    public function testCreateAndLinkAccountCreatesActivePasswordlessUserAndReturnsIdentity(): void
    {
        $this->service->setRandomByteGenerator(fn(int $n): string => str_repeat("\x00", $n));
        $userId = str_repeat('0', 32);
        $sub    = 'urn:fdc:gov.uk:2022:brand-new';
        $email  = 'brand.new.user@gmail.com';

        $this->userRepository->shouldReceive('create')
            ->once()
            ->with($userId, Mockery::on(function (array $details) use ($sub, $email): bool {
                return $details['identity'] === 'onelogin:' . $sub
                    && $details['password_hash'] === null
                    && $details['activation_token'] === null
                    && $details['active'] === true
                    && $details['activated'] instanceof DateTime
                    && $details['failed_login_attempts'] === 0
                    && $details['one_login_sub'] === $sub
                    && $details['one_login_email'] === $email
                    && $details['is_professional'] === true
                    && $details['profile'] instanceof ProfileUserModel
                    && $details['profile']->getName()?->getFirst() === 'Joe';
            }))
            ->andReturn(true);

        $this->userRepository->shouldNotReceive('setOneLoginSub');
        $this->userRepository->shouldReceive('updateLastLoginTime')->once()->with($userId);

        $user = Mockery::mock(UserInterface::class);
        $this->userRepository->shouldReceive('getById')->once()->with($userId)->andReturn($user);

        $expires = new DateTime('+4500 seconds');
        $this->authenticationService->shouldReceive('issueAuthToken')
            ->once()
            ->with($user)
            ->andReturn(['token' => 'tok-new', 'expiresIn' => 4500, 'expiresAt' => $expires]);

        $result = $this->service->createAndLinkAccount($sub, $email, UserType::Professional, self::PROFILE);

        $this->assertSame($userId, $result['userId']);
        $this->assertSame('tok-new', $result['token']);
        $this->assertSame($expires->format('c'), $result['tokenExpiresAt']);
        $this->assertNull($result['sharedSpaceId']);
        $this->assertNotEmpty($result['lastLogin']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidProfileProvider(): array
    {
        return [
            'no name'       => [['address' => self::PROFILE['address']]],
            'name not data' => [['name' => 'Joe Bloggs']],
            'dob not a date' => [['dob' => ['date' => 'not a date']] + self::PROFILE],
        ];
    }

    /**
     * @dataProvider invalidProfileProvider
     * @param array<string, mixed> $profile
     */
    public function testCreateAndLinkAccountRejectsAnInvalidProfileWithoutCreatingAnAccount(array $profile): void
    {
        $this->userRepository->shouldNotReceive('create');

        $this->expectException(InvalidArgumentException::class);

        $this->service->createAndLinkAccount('urn:fdc:gov.uk:2022:brand-new', 'new@example.com', UserType::Lay, $profile);
    }

    public function testBackChannelLogoutClearsAuthTokenForTheMatchingUser(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->with('a.logout.token')
            ->andReturn(['sub' => 'urn:fdc:gov.uk:2022:sub-abc', 'jti' => 'jti-1', 'iat' => self::SIGNED_OUT_AT]);

        $this->expectJtiRecorded('jti-1');

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn('user-1');

        $this->userRepository->shouldReceive('getByOneLoginSub')
            ->once()->with('urn:fdc:gov.uk:2022:sub-abc')->andReturn($user);

        $this->userRepository->shouldReceive('clearAuthToken')->once()->with('user-1');

        $result = $this->service->handleBackChannelLogout('a.logout.token');

        $this->assertSame(['accepted' => true, 'sub' => 'urn:fdc:gov.uk:2022:sub-abc', 'signedOutAt' => self::SIGNED_OUT_AT], $result);
    }

    public function testBackChannelLogoutRejectsInvalidTokenAndEndsNoSession(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->andThrow(new LogoutTokenException('invalid_signature'));

        $this->userRepository->shouldNotReceive('getByOneLoginSub');
        $this->userRepository->shouldNotReceive('clearAuthToken');
        $this->cache->shouldNotReceive('set');

        $result = $this->service->handleBackChannelLogout('forged.logout.token');

        $this->assertFalse($result['accepted']);
        $this->assertSame('invalid_signature', $result['reason']);
    }

    public function testBackChannelLogoutLetsAKeyFetchFailureThroughSoOneLoginCanRetry(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->andThrow(new RuntimeException('Unable to fetch One Login signing keys'));

        $this->cache->shouldNotReceive('set');
        $this->userRepository->shouldNotReceive('clearAuthToken');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch One Login signing keys');

        $this->service->handleBackChannelLogout('a.logout.token');
    }

    public function testBackChannelLogoutRejectsReplayedJti(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->andReturn(['sub' => 'urn:fdc:gov.uk:2022:sub-abc', 'jti' => 'jti-1', 'iat' => self::SIGNED_OUT_AT]);

        $this->cache->shouldReceive('has')->once()->andReturn(true);
        $this->cache->shouldNotReceive('set');

        $this->userRepository->shouldNotReceive('getByOneLoginSub');
        $this->userRepository->shouldNotReceive('clearAuthToken');

        $result = $this->service->handleBackChannelLogout('a.logout.token');

        $this->assertFalse($result['accepted']);
        $this->assertSame('replayed_jti', $result['reason']);
    }

    public function testBackChannelLogoutAcceptsValidTokenForUnknownSubject(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->andReturn(['sub' => 'urn:fdc:gov.uk:2022:stranger', 'jti' => 'jti-2', 'iat' => self::SIGNED_OUT_AT]);

        $this->expectJtiRecorded('jti-2');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn(null);
        $this->userRepository->shouldNotReceive('clearAuthToken');

        $result = $this->service->handleBackChannelLogout('a.logout.token');

        $this->assertSame(['accepted' => true, 'sub' => 'urn:fdc:gov.uk:2022:stranger', 'signedOutAt' => self::SIGNED_OUT_AT], $result);
    }

    public function testBackChannelLogoutDoesNotRecordJtiWhenTheLogoutFails(): void
    {
        $this->logoutTokenVerifier->shouldReceive('verify')
            ->once()
            ->andReturn(['sub' => 'urn:fdc:gov.uk:2022:sub-abc', 'jti' => 'jti-1', 'iat' => self::SIGNED_OUT_AT]);

        $this->cache->shouldReceive('has')->once()->andReturn(false);
        $this->cache->shouldNotReceive('set');

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn('user-1');

        $this->userRepository->shouldReceive('getByOneLoginSub')->once()->andReturn($user);
        $this->userRepository->shouldReceive('clearAuthToken')
            ->once()
            ->andThrow(new RuntimeException('database is on fire'));

        $this->expectException(RuntimeException::class);

        $this->service->handleBackChannelLogout('a.logout.token');
    }

    private function expectJtiRecorded(string $jti): void
    {
        $expectedKey = 'logout_jti_' . hash('sha256', $jti);

        $this->cache->shouldReceive('has')->once()->with($expectedKey)->andReturn(false);
        $this->cache->shouldReceive('set')->once()->with($expectedKey, true, 180);
    }

    private function makeLinkUser(?string $oneLoginSub): MockInterface|UserInterface
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('oneLoginSub')->andReturn($oneLoginSub);

        return $user;
    }

    private function stubClientForRequestObject(): void
    {
        $issuer = Mockery::mock(IssuerInterface::class);
        $issuer->shouldReceive('getMetadata')->andReturn(IssuerMetadata::fromArray([
            'issuer'                 => 'https://oidc.integration.account.gov.uk/',
            'authorization_endpoint' => self::AUTHORIZATION_ENDPOINT,
            'jwks_uri'               => 'https://oidc.integration.account.gov.uk/.well-known/jwks.json',
        ]));

        $this->oidcClient->shouldReceive('getIssuer')->andReturn($issuer);
        $this->oidcClient->shouldReceive('getMetadata')->andReturn(ClientMetadata::fromArray(['client_id' => self::CLIENT_ID]));
    }

    private function stubEndSessionEndpoint(?string $endpoint): void
    {
        $metadata = Mockery::mock(IssuerMetadataInterface::class);
        $metadata->shouldReceive('get')->with('end_session_endpoint')->andReturn($endpoint);

        $issuer = Mockery::mock(IssuerInterface::class);
        $issuer->shouldReceive('getMetadata')->andReturn($metadata);

        $this->oidcClient->shouldReceive('getIssuer')->andReturn($issuer);
    }

    /**
     * @param array<string, mixed> $claims overrides the ID token's default claims; a null value removes the claim
     */
    private function makeTokenSet(string $sub, array $claims = []): MockInterface|TokenSetInterface
    {
        $claims = array_filter(
            [...['sub' => $sub, 'vot' => 'Cl.Cm', 'iat' => self::SIGNED_IN_AT], ...$claims],
            static fn (mixed $value): bool => $value !== null,
        );

        $tokenSet = Mockery::mock(TokenSetInterface::class);
        $tokenSet->shouldReceive('getIdToken')->andReturn('header.payload.sig');
        $tokenSet->shouldReceive('claims')->andReturn($claims);

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

    private function makeUser(
        string $id,
        int $failedAttempts,
        DateTime $lastLogin,
        ?string $oneLoginEmail = null,
    ): MockInterface|UserInterface {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('id')->andReturn($id);
        $user->shouldReceive('failedLoginAttempts')->andReturn($failedAttempts);
        $user->shouldReceive('lastLoginAt')->andReturn($lastLogin);
        $user->shouldReceive('oneLoginEmail')->andReturn($oneLoginEmail);

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
