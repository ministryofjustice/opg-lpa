<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\Service\Cognito\Client as CognitoClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AlbSimulatorMiddleware implements MiddlewareInterface
{
    private const string AWS_COGNITO_HEADER = 'X-Amzn-Oidc-Data';

    public function __construct(
        private readonly CognitoClient $cognitoClient,
        private readonly string $devEmail,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader(self::AWS_COGNITO_HEADER)) {
            return $handler->handle($request);
        }

        $token = $this->cognitoClient->fetchTestToken($this->devEmail);

        return $handler->handle(
            $request->withHeader(self::AWS_COGNITO_HEADER, $token)
        );
    }
}
