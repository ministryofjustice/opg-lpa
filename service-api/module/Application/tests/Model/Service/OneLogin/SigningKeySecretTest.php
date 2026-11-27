<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\SigningKeySecret;
use Aws\CommandInterface;
use Aws\Result;
use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class SigningKeySecretTest extends MockeryTestCase
{
    private const SECRET_NAME = 'test/onelogin-signing-keys'; // pragma: allowlist secret

    private SecretsManagerClient&MockInterface $client;

    private CacheInterface $cache;

    public function setUp(): void
    {
        $this->client = Mockery::mock(SecretsManagerClient::class);
        $this->cache  = new Psr16Cache(new ArrayAdapter());
    }

    /**
     * @param array<string, mixed> $value
     */
    private function secretReturns(array $value): void
    {
        $this->client->shouldReceive('getSecretValue')
            ->with(['SecretId' => self::SECRET_NAME])
            ->once()
            ->andReturn(new Result(['SecretString' => json_encode($value)]));
    }

    private function secretStringReturns(string $value): void
    {
        $this->client->shouldReceive('getSecretValue')
            ->once()
            ->andReturn(new Result(['SecretString' => $value]));
    }

    private function signingKeySecret(): SigningKeySecret
    {
        return new SigningKeySecret($this->client, $this->cache, self::SECRET_NAME);
    }

    public function testSignsWithTheCurrentKeyFromTheSecret(): void
    {
        $this->secretReturns(['current' => KeyPairManagerTest::rsaPrivateKey('current')]);

        $jwks = $this->signingKeySecret()->keyPairManager()->publicJwks();

        $this->assertCount(1, $jwks['keys']);
    }

    public function testPublishesTheNextAndPreviousKeys(): void
    {
        $this->secretReturns([
            'current'  => KeyPairManagerTest::rsaPrivateKey('current'),
            'next'     => KeyPairManagerTest::rsaPrivateKey('next'),
            'previous' => KeyPairManagerTest::rsaPrivateKey('previous'),
        ]);

        $jwks = $this->signingKeySecret()->keyPairManager()->publicJwks();

        $this->assertCount(3, $jwks['keys']);
    }

    public function testAcceptsNullForAnEmptySlot(): void
    {
        $this->secretReturns(['current' => KeyPairManagerTest::rsaPrivateKey('current'), 'next' => null]);

        $jwks = $this->signingKeySecret()->keyPairManager()->publicJwks();

        $this->assertCount(1, $jwks['keys']);
    }

    public function testFetchesTheSecretOnceAndThenUsesTheCache(): void
    {
        $this->secretReturns(['current' => KeyPairManagerTest::rsaPrivateKey('current')]);

        $first  = $this->signingKeySecret()->keyPairManager()->jwk()->get('kid');
        $second = $this->signingKeySecret()->keyPairManager()->jwk()->get('kid');

        $this->assertSame($first, $second);
    }

    public function testCachesTheSecretForAnHour(): void
    {
        $secret = json_encode(['current' => KeyPairManagerTest::rsaPrivateKey('current')]);
        $this->secretStringReturns($secret);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->with(Mockery::type('string'), $secret, 3600)->once()->andReturn(true);

        (new SigningKeySecret($this->client, $cache, self::SECRET_NAME))->keyPairManager();
    }

    public function testInvalidJsonThrowsAndIsNotCached(): void
    {
        $this->secretStringReturns('{not json');

        try {
            $this->signingKeySecret()->keyPairManager();
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'OneLogin signing keys secret "test/onelogin-signing-keys" is not valid JSON',
                $e->getMessage(),
            );
        }

        $this->assertFalse($this->cache->has('onelogin-signing-keys'));
    }

    public function testMissingCurrentKeyThrows(): void
    {
        $this->secretReturns(['next' => KeyPairManagerTest::rsaPrivateKey('next')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin signing keys secret "test/onelogin-signing-keys" must have a "current" key');

        $this->signingKeySecret()->keyPairManager();
    }

    /**
     * A typo such as "nxet" would otherwise leave the new key unpublished, and sign-in
     * would break as soon as it became the current key.
     */
    public function testUnknownSlotThrows(): void
    {
        $this->secretReturns([
            'current' => KeyPairManagerTest::rsaPrivateKey('current'),
            'nxet'    => KeyPairManagerTest::rsaPrivateKey('next'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OneLogin signing keys secret "test/onelogin-signing-keys" has unexpected fields: nxet',
        );

        $this->signingKeySecret()->keyPairManager();
    }

    public function testNonStringSlotThrows(): void
    {
        $this->secretReturns(['current' => KeyPairManagerTest::rsaPrivateKey('current'), 'next' => 42]);

        $this->expectException(\TypeError::class);

        $this->signingKeySecret()->keyPairManager();
    }

    public function testInvalidKeyThrowsWithoutRevealingItAndIsNotCached(): void
    {
        $this->secretReturns(['current' => 'not-a-valid-pem-key']);

        try {
            $this->signingKeySecret()->keyPairManager();
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Failed to load OneLogin private key', $e->getMessage());
            $this->assertStringNotContainsString('not-a-valid-pem-key', $e->getMessage());
        }

        $this->assertFalse($this->cache->has('onelogin-signing-keys'));
    }

    public function testSecretsManagerErrorThrows(): void
    {
        $this->client->shouldReceive('getSecretValue')->andThrow(new SecretsManagerException(
            'Secrets Manager can not find the specified secret.',
            Mockery::mock(CommandInterface::class),
            ['code' => 'ResourceNotFoundException'],
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Could not fetch OneLogin signing keys secret "test/onelogin-signing-keys": ResourceNotFoundException',
        );

        $this->signingKeySecret()->keyPairManager();
    }

    public function testEmptySecretThrows(): void
    {
        $this->client->shouldReceive('getSecretValue')->andReturn(new Result([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OneLogin signing keys secret "test/onelogin-signing-keys" has no value');

        $this->signingKeySecret()->keyPairManager();
    }
}
