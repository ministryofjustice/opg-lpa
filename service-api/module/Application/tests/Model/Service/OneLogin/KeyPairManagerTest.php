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

    private static function ecPrivateKey(): string
    {
        $label = self::KEY_LABEL;

        return "-----BEGIN {$label}-----\n"
            . self::TEST_KEY_BODY
            . "-----END {$label}-----\n";
    }

    public static function rsaPrivateKey(): string
    {
        static $pem = null;

        if ($pem === null) {
            $resource = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);

            if ($resource === false) {
                self::fail('Unable to generate an RSA test key: ' . openssl_error_string());
            }

            openssl_pkey_export($resource, $pem);
        }

        return $pem;
    }

    public function testJwkHasExpectedAlgorithmAndUse(): void
    {
        $manager = new KeyPairManager(self::rsaPrivateKey(), 'test-kid-1');

        $serialised = $manager->jwk()->jsonSerialize();

        $this->assertSame('RSA', $serialised['kty']);
        $this->assertSame('RS256', $serialised['alg']);
        $this->assertSame('sig', $serialised['use']);
        $this->assertSame('test-kid-1', $serialised['kid']);
    }

    public function testJwkAcceptsBase64EncodedPem(): void
    {
        $manager = new KeyPairManager(base64_encode(self::rsaPrivateKey()), 'test-kid-b64');

        $serialised = $manager->jwk()->jsonSerialize();

        $this->assertSame('RSA', $serialised['kty']);
        $this->assertSame('RS256', $serialised['alg']);
        $this->assertSame('test-kid-b64', $serialised['kid']);
    }

    public function testRawPemAndBase64PemProduceSameKey(): void
    {
        $fromPem    = (new KeyPairManager(self::rsaPrivateKey(), 'k'))->jwk()->jsonSerialize();
        $fromBase64 = (new KeyPairManager(base64_encode(self::rsaPrivateKey()), 'k'))->jwk()->jsonSerialize();

        $this->assertSame($fromPem['n'], $fromBase64['n']);
        $this->assertSame($fromPem['d'], $fromBase64['d']);
    }

    /**
     * An EC key would sign happily, and the local mock would accept it, but GOV.UK One
     * Login does not support ES256 for the client assertion -- so it has to fail here.
     */
    public function testEcKeyIsRejected(): void
    {
        $manager = new KeyPairManager(self::ecPrivateKey(), 'test-kid-ec');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin private key must be RSA, got "EC"');

        $manager->jwk();
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

        new KeyPairManager(self::rsaPrivateKey(), '');
    }

    public function testMalformedKeyThrows(): void
    {
        $manager = new KeyPairManager('not-a-valid-pem-key', 'test-kid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load OneLogin private key');

        $manager->jwk();
    }
}
