<?php

declare(strict_types=1);

namespace App\Service\Alb;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class PublicKeyClient
{
    private const string CACHE_KEY_PREFIX = 'alb_public_key_';

    /**
     * @param string $baseUrl In production this is AWS's regional public key endpoint
     *                        (https://public-keys.auth.elb.<region>.amazonaws.com); in local
     *                        development it points at the mock ALB/Cognito server instead, so
     *                        the app validates the exact same way in both environments.
     * @param int $cacheTtl   Seconds to cache each fetched key in APCu (shared across all FPM
     *                        workers). AWS's ALB signing keys rotate infrequently, so a long
     *                        TTL is safe. 0 disables caching (e.g. in tests).
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly int $cacheTtl = 86400,
    ) {
    }

    /**
     * Fetches (and caches) the PEM-encoded public key that an Application Load Balancer
     * uses to sign the `X-Amzn-Oidc-Data` header, identified by the "kid" from the JWT
     * header.
     *
     * @see https://docs.aws.amazon.com/elasticloadbalancing/latest/application/listener-authenticate-users.html#user-claims-encoding
     */
    public function fetchPublicKey(string $keyId): string
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $keyId;

        if ($this->cacheTtl > 0 && function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_string($cached)) {
                return $cached;
            }
        }

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    RequestMethodInterface::METHOD_GET,
                    rtrim($this->baseUrl, '/') . '/' . $keyId,
                )
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to fetch ALB public key', 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                sprintf('Unexpected status %d fetching ALB public key for kid "%s"', $response->getStatusCode(), $keyId)
            );
        }

        $key = trim((string) $response->getBody());

        if ($this->cacheTtl > 0 && function_exists('apcu_store')) {
            apcu_store($cacheKey, $key, $this->cacheTtl);
        }

        return $key;
    }
}
