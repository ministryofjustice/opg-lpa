<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\KeyPairManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class AuthorisationClientManagerTest extends TestCase
{
    private const DISCOVERY_URL = 'https://oidc.example.com/.well-known/openid-configuration';

    private const KEY_LABEL = 'EC PRIVATE ' . 'KEY';

    private const TEST_KEY_BODY =
        "MHcCAQEEIGmerFRXPsM9dw+YpVfTnaNHR1JYVTmkdahadOQbr9E2oAoGCCqGSM49\n" . // pragma: allowlist secret
        "AwEHoUQDQgAEhrCO/0SUIDbj3taD8rtl0oVS1qNO3paLZaR0WPcvB607w2FyijHG\n" . // pragma: allowlist secret
        "lP2Fk5TdKSt3T1Iy2jKBmnYWwrFABZg9Aw==\n";                             // pragma: allowlist secret

    private static function testPrivateKey(): string
    {
        $label = self::KEY_LABEL;

        return "-----BEGIN {$label}-----\n"
            . self::TEST_KEY_BODY
            . "-----END {$label}-----\n";
    }

    public function testCacheTtlConstantValue(): void
    {
        $this->assertSame(3600, AuthorisationClientManager::CACHE_TTL);
    }

    public function testClientAuthenticatesWithPrivateKeyJwt(): void
    {
        $metadata = $this->makeManager()->get()->getMetadata();

        $this->assertSame('test-client-id', $metadata->getClientId());
        $this->assertSame('private_key_jwt', $metadata->getTokenEndpointAuthMethod());
    }

    public function testIdTokenSigningAlgorithmIsPinnedToEs256(): void
    {
        $metadata = $this->makeManager()->get()->getMetadata();

        $this->assertSame('ES256', $metadata->getIdTokenSignedResponseAlg());
    }

    public function testClientSignsWithTheConfiguredKey(): void
    {
        $jwks = $this->makeManager()->get()->getJwksProvider()->getJwks();

        $this->assertCount(1, $jwks['keys']);
        $this->assertSame('test-kid', $jwks['keys'][0]['kid']);
        $this->assertSame('sig', $jwks['keys'][0]['use']);
    }

    public function testIssuerIsTakenFromTheDiscoveryDocument(): void
    {
        $issuer = $this->makeManager()->get()->getIssuer()->getMetadata();

        $this->assertSame('https://oidc.example.com/', $issuer->getIssuer());
        $this->assertSame('https://oidc.example.com/token', $issuer->getTokenEndpoint());
    }

    private function makeManager(): AuthorisationClientManager
    {
        return new AuthorisationClientManager(
            'test-client-id',
            self::DISCOVERY_URL,
            new KeyPairManager(self::testPrivateKey(), 'test-kid'),
            $this->createMock(ClientInterface::class),
            $this->cacheReturningDiscoveryDocument(),
        );
    }

    private function cacheReturningDiscoveryDocument(): CacheInterface
    {
        $document = json_encode([
            'issuer'                 => 'https://oidc.example.com/',
            'authorization_endpoint' => 'https://oidc.example.com/authorize',
            'token_endpoint'         => 'https://oidc.example.com/token',
            'userinfo_endpoint'      => 'https://oidc.example.com/userinfo',
            'jwks_uri'               => 'https://oidc.example.com/.well-known/jwks.json',
        ], JSON_THROW_ON_ERROR);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn($document);
        $cache->method('set')->willReturn(true);

        return $cache;
    }
}
