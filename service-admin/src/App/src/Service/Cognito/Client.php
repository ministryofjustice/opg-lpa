<?php

declare(strict_types=1);

namespace App\Service\Cognito;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class Client
{
    private const string JWKS_CACHE_KEY = 'cognito_jwks';

    /**
     * @param int $jwksCacheTtl Seconds to cache JWKS in APCu. 0 disables caching (e.g. in tests).
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly int $jwksCacheTtl = 3600,
    ) {
    }

    /**
     * Fetches the JWKS key set from the Cognito JWKS endpoint.
     * Cached in APCu (shared across all FPM workers) for $jwksCacheTtl seconds.
     * Pass $forceRefresh = true to bypass the cache after a key rotation.
     *
     * @return array<string, mixed>
     */
    public function fetchJwks(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && $this->jwksCacheTtl > 0 && function_exists('apcu_fetch')) {
            $cached = apcu_fetch(self::JWKS_CACHE_KEY, $success);
            if ($success) {
                return $cached;
            }
        }

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    RequestMethodInterface::METHOD_GET,
                    $this->baseUrl . '/.well-known/jwks.json',
                    ['Content-Type' => 'application/json']
                )
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to fetch JWKS from Cognito', 0, $e);
        }

        $jwks = json_decode($response->getBody()->getContents(), true);

        if ($this->jwksCacheTtl > 0 && function_exists('apcu_store')) {
            apcu_store(self::JWKS_CACHE_KEY, $jwks, $this->jwksCacheTtl);
        }

        return $jwks;
    }

    /**
     * Requests a test token from the mock Cognito server for a given email.
     * Used by AlbSimulatorMiddleware in local development only.
     */
    public function fetchTestToken(string $email): string
    {
        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    RequestMethodInterface::METHOD_POST,
                    $this->baseUrl . '/test/token',
                    ['Content-Type' => 'application/json'],
                    json_encode(['email' => $email])
                )
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to fetch test token from mock Cognito', 0, $e);
        }

        return json_decode($response->getBody()->getContents(), true)['id_token'];
    }
}
