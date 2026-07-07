<?php

declare(strict_types=1);

namespace MakeShared\Service;

use Aws\SecretsManager\SecretsManagerClient;
use RuntimeException;

/**
 * Resolves a secret value from AWS Secrets Manager at runtime.
 *
 * Usage:
 *   $secret = SecretService::resolve(
 *       arn: getenv('MY_SECRET_ARN') ?: null,
 *   );
 *
 * The resolved value is cached in APCu for 5 minutes so that secret rotation
 * takes effect without a service restart.
 */
class SecretService
{
    private const int CACHE_TTL = 300; // 5 minutes — short enough for rotation to take effect

    /**
     * @param string|null $arn      Secrets Manager ARN or name.
     * @param string      $region   AWS region (defaults to eu-west-1).
     * @param string|null $endpoint Optional endpoint URL override (e.g. localstack).
     */
    public static function resolve(
        ?string $arn,
        string $region = 'eu-west-1',
        ?string $endpoint = null,
    ): string {
        if ($arn === null) {
            throw new RuntimeException(
                'No secret ARN configured. Set the _ARN env var.'
            );
        }

        $cacheKey = 'secret_' . md5($arn);

        if (function_exists('apcu_fetch')) {
            // $success is set by reference by apcu_fetch — true on cache hit, false on miss.
            // We cannot rely on the return value alone since false is a valid cached value.
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_string($cached)) {
                return $cached;
            }
        }

        $value = self::fetchFromSecretsManager($arn, $region, $endpoint);

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $value, self::CACHE_TTL);
        }

        return $value;
    }

    private static function fetchFromSecretsManager(string $arn, string $region, ?string $endpoint): string
    {
        $config = [
            'version' => 'latest',
            'region'  => $region,
        ];

        if ($endpoint !== null) {
            $config['endpoint'] = $endpoint;
        }

        $client = static::createClient($config);

        $result = $client->getSecretValue(['SecretId' => $arn]);

        $value = $result->get('SecretString');

        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('Secrets Manager returned empty value for ARN: %s', $arn));
        }

        return $value;
    }

    /**
     * Creates a SecretsManagerClient. Extracted to allow overriding in tests.
     *
     * @param array<string, mixed> $config
     */
    protected static function createClient(array $config): SecretsManagerClient
    {
        return new SecretsManagerClient($config);
    }
}
