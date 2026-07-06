<?php

declare(strict_types=1);

namespace App\Service\Cognito;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class Client
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Fetches the JWKS key set from the Cognito JWKS endpoint.
     * Used by AlbOidcMiddleware to validate the ALB-injected JWT.
     *
     * @return array<string, mixed>
     */
    public function fetchJwks(): array
    {
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

        return json_decode($response->getBody()->getContents(), true);
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
