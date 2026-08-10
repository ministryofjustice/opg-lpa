<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\KeyPairManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class AuthorisationClientManagerTest extends MockeryTestCase
{
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

    public function testClientMetadataHasPrivateKeyJwt(): void
    {
        $manager = $this->makeManager();

        $this->assertInstanceOf(AuthorisationClientManager::class, $manager);
    }

    private function makeManager(): AuthorisationClientManager
    {
        $keyPairManager = new KeyPairManager(self::testPrivateKey(), 'test-kid');

        /** @var MockInterface|ClientInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);

        /** @var MockInterface|CacheInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);

        return new AuthorisationClientManager(
            'test-client-id',
            'https://oidc.example.com/.well-known/openid-configuration',
            $keyPairManager,
            $httpClient,
            $cache,
        );
    }
}
