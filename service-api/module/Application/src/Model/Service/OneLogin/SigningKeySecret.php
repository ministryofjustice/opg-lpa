<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use JsonException;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * Reads our One Login signing keys from Secrets Manager, rather than an environment
 * variable that anything running on the container could read.
 *
 * The secret is JSON: {"current": "<pem>", "next": "<pem>", "previous": "<pem>"}, where
 * only "current" is required. See docs/runbooks/onelogin/rotating_the_signing_key.md.
 */
class SigningKeySecret
{
    public const int CACHE_TTL_SECONDS = 3600;

    private const string CACHE_KEY = 'onelogin-signing-keys';

    private const array SLOTS = ['current', 'next', 'previous'];

    public function __construct(
        private readonly SecretsManagerClient $client,
        private readonly CacheInterface $cache,
        private readonly string $secretName,
    ) {
    }

    /**
     * The secret is only cached once it is valid, so a bad value is fetched again on the
     * next request.
     *
     * @psalm-suppress PossiblyUnusedMethod Called by the KeyPairManager factory in module.config.php
     */
    public function keyPairManager(): KeyPairManager
    {
        $cached = $this->cache->get(self::CACHE_KEY);

        if (is_string($cached)) {
            return $this->build($cached);
        }

        $secret         = $this->fetch();
        $keyPairManager = $this->build($secret);

        $this->cache->set(self::CACHE_KEY, $secret, self::CACHE_TTL_SECONDS);

        return $keyPairManager;
    }

    private function fetch(): string
    {
        try {
            $secret = $this->client->getSecretValue(['SecretId' => $this->secretName])->get('SecretString');
        } catch (AwsException $e) {
            throw new RuntimeException(
                sprintf(
                    'Could not fetch OneLogin signing keys secret "%s": %s',
                    $this->secretName,
                    $e->getAwsErrorCode() ?? $e->getMessage(),
                ),
                0,
                $e,
            );
        }

        if (!is_string($secret) || $secret === '') {
            throw new RuntimeException(sprintf('OneLogin signing keys secret "%s" has no value', $this->secretName));
        }

        return $secret;
    }

    private function build(#[\SensitiveParameter] string $secret): KeyPairManager
    {
        try {
            $keys = json_decode($secret, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException(sprintf('OneLogin signing keys secret "%s" is not valid JSON', $this->secretName));
        }

        if (!is_array($keys) || !isset($keys['current'])) {
            throw new RuntimeException(
                sprintf('OneLogin signing keys secret "%s" must have a "current" key', $this->secretName),
            );
        }

        $unexpected = array_diff(array_keys($keys), self::SLOTS);

        if ($unexpected !== []) {
            throw new RuntimeException(sprintf(
                'OneLogin signing keys secret "%s" has unexpected fields: %s',
                $this->secretName,
                implode(', ', $unexpected),
            ));
        }

        /** @var array{current: string, next?: string|null, previous?: string|null} $keys */
        return new KeyPairManager($keys['current'], $keys['next'] ?? null, $keys['previous'] ?? null);
    }
}
