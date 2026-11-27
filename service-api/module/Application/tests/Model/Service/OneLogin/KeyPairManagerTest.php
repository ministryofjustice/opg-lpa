<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\KeyPairManager;
use Jose\Component\Core\Util\Base64UrlSafe;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class KeyPairManagerTest extends TestCase
{
    private const KEY_LABEL = 'EC PRIVATE ' . 'KEY';

    private const TEST_KEY_BODY =
        "MHcCAQEEIGmerFRXPsM9dw+YpVfTnaNHR1JYVTmkdahadOQbr9E2oAoGCCqGSM49\n" . // pragma: allowlist secret
        "AwEHoUQDQgAEhrCO/0SUIDbj3taD8rtl0oVS1qNO3paLZaR0WPcvB607w2FyijHG\n" . // pragma: allowlist secret
        "lP2Fk5TdKSt3T1Iy2jKBmnYWwrFABZg9Aw==\n";                             // pragma: allowlist secret

    /** @var array<string, string> */
    private static array $generatedKeys = [];

    private static function ecPrivateKey(): string
    {
        $label = self::KEY_LABEL;

        return "-----BEGIN {$label}-----\n"
            . self::TEST_KEY_BODY
            . "-----END {$label}-----\n";
    }

    public static function rsaPrivateKey(string $name = 'current'): string
    {
        if (!isset(self::$generatedKeys[$name])) {
            $resource = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);

            if ($resource === false) {
                self::fail('Unable to generate an RSA test key: ' . openssl_error_string());
            }

            openssl_pkey_export($resource, $pem);
            self::$generatedKeys[$name] = $pem;
        }

        return self::$generatedKeys[$name];
    }

    private static function expectedKeyId(string $pem): string
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($pem));

        $members = json_encode([
            'e'   => Base64UrlSafe::encodeUnpadded($details['rsa']['e']),
            'kty' => 'RSA',
            'n'   => Base64UrlSafe::encodeUnpadded($details['rsa']['n']),
        ], JSON_UNESCAPED_SLASHES);

        return Base64UrlSafe::encodeUnpadded(hash('sha256', $members, true));
    }

    public function testJwkHasExpectedAlgorithmAndUse(): void
    {
        $serialised = (new KeyPairManager(self::rsaPrivateKey()))->jwk()->jsonSerialize();

        $this->assertSame('RSA', $serialised['kty']);
        $this->assertSame('RS256', $serialised['alg']);
        $this->assertSame('sig', $serialised['use']);
    }

    public function testKeyIdIsTheThumbprintOfTheKey(): void
    {
        $jwk = (new KeyPairManager(self::rsaPrivateKey()))->jwk();

        $this->assertSame(self::expectedKeyId(self::rsaPrivateKey()), $jwk->get('kid'));
    }

    public function testDifferentKeysGetDifferentKeyIds(): void
    {
        $first  = (new KeyPairManager(self::rsaPrivateKey('current')))->jwk()->get('kid');
        $second = (new KeyPairManager(self::rsaPrivateKey('next')))->jwk()->get('kid');

        $this->assertNotSame($first, $second);
    }

    public function testJwkAcceptsBase64EncodedPem(): void
    {
        $fromPem    = (new KeyPairManager(self::rsaPrivateKey()))->jwk()->jsonSerialize();
        $fromBase64 = (new KeyPairManager(base64_encode(self::rsaPrivateKey())))->jwk()->jsonSerialize();

        $this->assertSame($fromPem['n'], $fromBase64['n']);
        $this->assertSame($fromPem['d'], $fromBase64['d']);
        $this->assertSame($fromPem['kid'], $fromBase64['kid']);
    }

    public function testSignsWithTheCurrentKeyWhenOthersArePublished(): void
    {
        $manager = new KeyPairManager(
            self::rsaPrivateKey('current'),
            self::rsaPrivateKey('next'),
            self::rsaPrivateKey('previous'),
        );

        $this->assertSame(self::expectedKeyId(self::rsaPrivateKey('current')), $manager->jwk()->get('kid'));
    }

    public function testPublicJwksPublishesEveryKeyWithTheCurrentOneFirst(): void
    {
        $jwks = (new KeyPairManager(
            self::rsaPrivateKey('current'),
            self::rsaPrivateKey('next'),
            self::rsaPrivateKey('previous'),
        ))->publicJwks();

        $this->assertSame(
            [
                self::expectedKeyId(self::rsaPrivateKey('current')),
                self::expectedKeyId(self::rsaPrivateKey('next')),
                self::expectedKeyId(self::rsaPrivateKey('previous')),
            ],
            array_column($jwks['keys'], 'kid'),
        );
    }

    public function testPublicJwksHoldsOnlyThePublicHalfOfEachKey(): void
    {
        $jwks = (new KeyPairManager(self::rsaPrivateKey('current'), self::rsaPrivateKey('next')))->publicJwks();

        $this->assertCount(2, $jwks['keys']);

        foreach ($jwks['keys'] as $key) {
            $this->assertSame('RSA', $key['kty']);
            $this->assertSame('RS256', $key['alg']);
            $this->assertSame('sig', $key['use']);

            foreach (['d', 'p', 'q', 'dp', 'dq', 'qi'] as $privateParameter) {
                $this->assertArrayNotHasKey($privateParameter, $key);
            }
        }
    }

    public function testPublicJwksHoldsOnlyTheCurrentKeyWhenNoOthersAreSet(): void
    {
        $jwks = (new KeyPairManager(self::rsaPrivateKey()))->publicJwks();

        $this->assertCount(1, $jwks['keys']);
    }

    /**
     * An EC key would sign happily, and the local mock would accept it, but GOV.UK One
     * Login does not support ES256 for the client assertion -- so it has to fail here.
     */
    public function testEcKeyIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin private key must be RSA, got "EC"');

        new KeyPairManager(self::ecPrivateKey());
    }

    public function testInvalidNextKeyIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load OneLogin private key');

        new KeyPairManager(self::rsaPrivateKey(), 'not-a-valid-pem-key');
    }

    public function testInvalidPreviousKeyIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin private key must be RSA');

        new KeyPairManager(self::rsaPrivateKey(), null, self::ecPrivateKey());
    }

    public function testTheSameKeyInTwoSlotsIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin signing keys must all be different');

        new KeyPairManager(self::rsaPrivateKey(), self::rsaPrivateKey());
    }

    public function testEmptyPrivateKeyThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin private key must not be empty');

        new KeyPairManager('');
    }

    public function testMalformedKeyThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load OneLogin private key');

        new KeyPairManager('not-a-valid-pem-key');
    }
}
