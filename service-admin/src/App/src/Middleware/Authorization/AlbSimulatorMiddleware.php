<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AlbSimulatorMiddleware implements MiddlewareInterface
{
    private const string AWS_COGNITO_HEADER = 'X-Amzn-Oidc-Data';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $mockCognitoUrl,
        private readonly string $devEmail,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader(self::AWS_COGNITO_HEADER)) {
            return $handler->handle($request);
        }

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    RequestMethodInterface::METHOD_POST,
                    $this->mockCognitoUrl . '/test/token',
                    ['Content-Type' => 'application/json'],
                    json_encode(['email' => $this->devEmail])
                )
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error communicating with mock Cognito service', 0, $e);
        }

        $token = json_decode($response->getBody()->getContents(), true)['id_token'];

        $request = $request->withHeader(self::AWS_COGNITO_HEADER, $token);
        return $handler->handle($request);
    }
}
