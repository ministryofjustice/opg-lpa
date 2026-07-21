<?php

declare(strict_types=1);

namespace App\Service\Alb;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Client for the mock ALB/Cognito server (see mock-cognito/), used by AlbSimulatorMiddleware
 * in local development and tests ONLY. Real (deployed) environments never construct this
 * class: authentication there is handled entirely by the ALB's authenticate-oidc action, and
 * requests are verified by AlbOidcMiddleware/PublicKeyClient instead.
 */
class MockAlbTokenClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * Requests a test token from the mock ALB/Cognito server for a given email, in the
     * exact same shape (ES256, "signer"/"client"/"kid" header fields) a real Application
     * Load Balancer produces for its X-Amzn-Oidc-Data header.
     */
    public function fetchTestToken(#[\SensitiveParameter] string $email): string
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
            throw new RuntimeException('Failed to fetch test token from mock ALB/Cognito server', 0, $e);
        }

        return json_decode($response->getBody()->getContents(), true)['token'];
    }
}
