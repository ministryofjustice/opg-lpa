<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\KeyPairManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class KeyPairManagerTest extends TestCase
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

    public function testJwkHasExpectedAlgorithmAndUse(): void
    {
        $manager = new KeyPairManager(self::testPrivateKey(), 'test-kid-1');
        $jwk     = $manager->jwk();

        $serialised = $jwk->jsonSerialize();

        $this->assertSame('ES256', $serialised['alg']);
        $this->assertSame('sig', $serialised['use']);
        $this->assertSame('test-kid-1', $serialised['kid']);
    }

    public function testJwkIsEcKeyType(): void
    {
        $manager = new KeyPairManager(self::testPrivateKey(), 'test-kid-2');
        $jwk     = $manager->jwk();

        $this->assertSame('EC', $jwk->jsonSerialize()['kty']);
    }

    public function testEmptyPrivateKeyThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('private key');

        new KeyPairManager('', 'some-kid');
    }

    public function testEmptyKeyIdThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('key ID');

        new KeyPairManager(self::testPrivateKey(), '');
    }

    public function testMalformedKeyThrows(): void
    {
        $manager = new KeyPairManager('not-a-valid-pem-key', 'test-kid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load OneLogin private key');

        $manager->jwk();
    }
}
