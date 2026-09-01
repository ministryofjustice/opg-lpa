<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\LogoutTokenException;
use Application\Model\Service\OneLogin\LogoutTokenVerifier;
use Facile\JoseVerifier\JWK\JwksProviderInterface;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadataInterface;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

class LogoutTokenVerifierTest extends MockeryTestCase
{
    private const ISSUER    = 'https://oidc.example.gov.uk/';
    private const CLIENT_ID = 'theClientId';
    private const KID       = 'test-key-1';
    private const EVENT     = 'http://schemas.openid.net/event/backchannel-logout';

    private JWK $signingKey;
    private JWK $publicKey;
    private MockInterface|CacheInterface $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->signingKey = JWKFactory::createECKey('P-256', ['alg' => 'ES256', 'kid' => self::KID]);
        $this->publicKey  = $this->signingKey->toPublic();
    }

    public function testValidTokenReturnsSubAndJti(): void
    {
        $verifier = $this->verifierWithJwks([$this->publicKey->all()]);

        $result = $verifier->verify($this->token());

        $this->assertSame('urn:fdc:gov.uk:2022:sub-abc', $result['sub']);
        $this->assertSame('jti-1', $result['jti']);
    }

    public function testRejectsAlgNone(): void
    {
        $unsigned = $this->unsignedToken();

        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()])->verify($unsigned);
    }

    public function testRejectsHmacSignedWithThePublicKey(): void
    {
        $publicKeyAsSecret = new JWK([
            'kty' => 'oct',
            'k'   => rtrim(strtr(base64_encode(json_encode($this->publicKey->all())), '+/', '-_'), '='),
            'kid' => self::KID,
        ]);

        $forged = $this->sign($this->claims(), $publicKeyAsSecret, new HS256(), ['alg' => 'HS256', 'kid' => self::KID]);

        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()])->verify($forged);
    }

    public function testRejectsSignatureFromADifferentKey(): void
    {
        $otherKey = JWKFactory::createECKey('P-256', ['alg' => 'ES256', 'kid' => self::KID]);
        $forged   = $this->sign($this->claims(), $otherKey, new ES256(), ['alg' => 'ES256', 'kid' => self::KID]);

        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()])->verify($forged);
    }

    public function testRejectsUnknownKid(): void
    {
        $token = $this->sign($this->claims(), $this->signingKey, new ES256(), ['alg' => 'ES256', 'kid' => 'not-our-key']);

        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()], reloadReturns: [])->verify($token);
    }

    public function testRejectsHeaderAlgThatDoesNotMatchTheKey(): void
    {
        $rsaKey = JWKFactory::createRSAKey(2048, ['alg' => 'RS256', 'kid' => self::KID]);
        $token  = $this->sign($this->claims(), $rsaKey, new RS256(), ['alg' => 'RS256', 'kid' => self::KID]);

        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()])->verify($token);
    }

    public function testRejectsMalformedToken(): void
    {
        $this->expectRejection('invalid_token');
        $this->verifierWithJwks([$this->publicKey->all()])->verify('not-a-jwt');
    }

    public function testRejectsAnEmptyClientId(): void
    {
        $verifier = $this->verifierWithJwks([$this->publicKey->all()], clientId: '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OneLogin client id is not configured');

        $verifier->verify($this->token());
    }

    /**
     * @dataProvider badClaimsProvider
     */
    public function testRejectsBadClaims(array $claims, string $expectedReason): void
    {
        $token = $this->sign($claims, $this->signingKey, new ES256(), ['alg' => 'ES256', 'kid' => self::KID]);

        $this->expectRejection($expectedReason);
        $this->verifierWithJwks([$this->publicKey->all()])->verify($token);
    }

    public static function badClaimsProvider(): array
    {
        $base = [
            'iss'    => self::ISSUER,
            'aud'    => self::CLIENT_ID,
            'sub'    => 'urn:fdc:gov.uk:2022:sub-abc',
            'iat'    => time() - 10,
            'exp'    => time() + 300,
            'jti'    => 'jti-1',
            'events' => [self::EVENT => []],
        ];

        return [
            'wrong issuer'        => [['iss' => 'https://evil.example/'] + $base, 'invalid_token'],
            'wrong audience'      => [['aud' => 'someone-elses-client'] + $base, 'invalid_token'],
            'audience array miss' => [['aud' => ['a', 'b']] + $base, 'invalid_token'],
            'iat in the future'   => [['iat' => time() + 600] + $base, 'invalid_token'],
            // Comfortably beyond the clock-skew leeway - a token one second past expiry is
            // now deliberately tolerated, see testToleratesSmallClockDrift().
            'already expired'     => [['exp' => time() - 3600] + $base, 'invalid_token'],
            'missing sub'         => [array_diff_key($base, ['sub' => null]), 'invalid_token'],
            'missing jti'         => [array_diff_key($base, ['jti' => null]), 'missing_jti'],
            'missing events'      => [array_diff_key($base, ['events' => null]), 'invalid_events'],
            'wrong event key'     => [['events' => ['http://example/other' => []]] + $base, 'invalid_events'],
            'event not an object' => [['events' => [self::EVENT => 'nope']] + $base, 'invalid_events'],
            // OIDC Back-Channel Logout 1.0: "A nonce Claim MUST NOT be present".
            'nonce present'       => [['nonce' => 'abc'] + $base, 'nonce_present'],
        ];
    }

    public function testReloadsJwksWhenKidIsNotInTheCachedSet(): void
    {
        $verifier = $this->verifierWithJwks([], reloadReturns: [$this->publicKey->all()]);

        $result = $verifier->verify($this->token());

        $this->assertSame('urn:fdc:gov.uk:2022:sub-abc', $result['sub']);
    }

    public function testAcceptsPopulatedEventObject(): void
    {
        $token = $this->sign(
            $this->claims(['events' => [self::EVENT => ['version' => 2]]]),
            $this->signingKey,
            new ES256(),
            ['alg' => 'ES256', 'kid' => self::KID],
        );

        $result = $this->verifierWithJwks([$this->publicKey->all()])->verify($token);

        $this->assertSame('urn:fdc:gov.uk:2022:sub-abc', $result['sub']);
    }

    public function testToleratesSmallClockDrift(): void
    {
        $verifier = $this->verifierWithJwks([$this->publicKey->all()]);

        $this->assertSame(
            'urn:fdc:gov.uk:2022:sub-abc',
            $verifier->verify($this->token(['iat' => time() + 30]))['sub'],
        );

        $this->assertSame(
            'urn:fdc:gov.uk:2022:sub-abc',
            $verifier->verify($this->token(['exp' => time() - 30]))['sub'],
        );
    }

    public function testStillRejectsClearlySkewedTimestamps(): void
    {
        $verifier = $this->verifierWithJwks([$this->publicKey->all()]);

        $this->expectRejection('invalid_token');
        $verifier->verify($this->token(['exp' => time() - 3600]));
    }

    public function testDoesNotRefetchJwksWhileCooldownIsActive(): void
    {
        $verifier = $this->verifierWithJwks([], reloadReturns: [$this->publicKey->all()], reloadOnCooldown: true);

        $this->expectRejection('invalid_token');
        $verifier->verify($this->token());
    }

    public function testSetsCooldownWhenItRefetchesJwks(): void
    {
        $verifier = $this->verifierWithJwks([], reloadReturns: [$this->publicKey->all()]);

        $this->cache->shouldReceive('set')
            ->once()
            ->with('onelogin_jwks_reload_cooldown', true, 60);

        $verifier->verify($this->token());
    }

    public function testRejectsValidSignatureUnderAnAlgorithmWeDoNotExpect(): void
    {
        $rsaKey = JWKFactory::createRSAKey(2048, ['alg' => 'RS256', 'kid' => 'rsa-key']);

        $token = $this->sign(
            $this->claims(),
            $rsaKey,
            new RS256(),
            ['alg' => 'RS256', 'kid' => 'rsa-key'],
        );

        $verifier = $this->verifierWithJwks([$rsaKey->toPublic()->all()]);

        $this->expectRejection('invalid_token');
        $verifier->verify($token);
    }

    private function expectRejection(string $reason): void
    {
        $this->expectException(LogoutTokenException::class);
        $this->expectExceptionMessage($reason);
    }

    /**
     * @param list<array<string, mixed>> $keys
     * @param list<array<string, mixed>>|null $reloadReturns
     */
    /**
     * @param list<array<string, mixed>> $keys
     * @param list<array<string, mixed>>|null $reloadReturns
     */
    private function verifierWithJwks(
        array $keys,
        ?array $reloadReturns = null,
        bool $reloadOnCooldown = false,
        string $clientId = self::CLIENT_ID,
    ): LogoutTokenVerifier {
        $jwksProvider = Mockery::mock(JwksProviderInterface::class);
        $jwksProvider->shouldReceive('getJwks')->andReturn(['keys' => $keys])->byDefault();

        $reloaded = Mockery::mock(JwksProviderInterface::class);
        $reloaded->shouldReceive('getJwks')->andReturn(['keys' => $reloadReturns ?? $keys]);
        $jwksProvider->shouldReceive('reload')->andReturn($reloaded)->byDefault();

        $issuerMetadata = Mockery::mock(IssuerMetadataInterface::class);
        $issuerMetadata->shouldReceive('toArray')->andReturn([
            'issuer'   => self::ISSUER,
            'jwks_uri' => self::ISSUER . '.well-known/jwks.json',
        ]);

        $issuer = Mockery::mock(IssuerInterface::class);
        $issuer->shouldReceive('getJwksProvider')->andReturn($jwksProvider);
        $issuer->shouldReceive('getMetadata')->andReturn($issuerMetadata);

        $clientMetadata = Mockery::mock(ClientMetadataInterface::class);
        $clientMetadata->shouldReceive('getClientId')->andReturn($clientId);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('getIssuer')->andReturn($issuer);
        $client->shouldReceive('getMetadata')->andReturn($clientMetadata);

        $clientManager = Mockery::mock(AuthorisationClientManager::class);
        $clientManager->shouldReceive('get')->andReturn($client);

        $this->cache = Mockery::mock(CacheInterface::class);
        $this->cache->shouldReceive('has')->andReturn($reloadOnCooldown)->byDefault();
        $this->cache->shouldReceive('set')->byDefault();

        return new LogoutTokenVerifier($clientManager, $this->cache);
    }

    private function claims(array $overrides = []): array
    {
        return $overrides + [
                'iss'    => self::ISSUER,
                'aud'    => self::CLIENT_ID,
                'sub'    => 'urn:fdc:gov.uk:2022:sub-abc',
                'iat'    => time() - 10,
                'exp'    => time() + 300,
                'jti'    => 'jti-1',
                'events' => [self::EVENT => []],
            ];
    }

    private function token(array $overrides = []): string
    {
        return $this->sign(
            $this->claims($overrides),
            $this->signingKey,
            new ES256(),
            ['alg' => 'ES256', 'kid' => self::KID],
        );
    }

    private function sign(array $claims, JWK $key, object $algorithm, array $header): string
    {
        $builder = new JWSBuilder(new AlgorithmManager([$algorithm]));

        $jws = $builder->create()
            ->withPayload(json_encode($claims, JSON_THROW_ON_ERROR))
            ->addSignature($key, $header)
            ->build();

        return (new CompactSerializer())->serialize($jws, 0);
    }

    private function unsignedToken(): string
    {
        $encode = static fn(array $part): string => rtrim(
            strtr(base64_encode(json_encode($part, JSON_THROW_ON_ERROR)), '+/', '-_'),
            '=',
        );

        return $encode(['alg' => 'none', 'kid' => self::KID]) . '.' . $encode($this->claims()) . '.';
    }
}
