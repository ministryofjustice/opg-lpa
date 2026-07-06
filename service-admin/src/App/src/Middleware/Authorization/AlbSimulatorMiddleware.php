<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\Service\Cognito\Client as CognitoClient;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AlbSimulatorMiddleware implements MiddlewareInterface
{
    private const string AWS_COGNITO_HEADER = 'X-Amzn-Oidc-Data';
    private const string SIGNED_OUT_SESSION_KEY = 'signed_out';

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

        $session = $request->getAttribute(SessionInterface::class);
        $isSignedOut = $session?->get(self::SIGNED_OUT_SESSION_KEY, false);

        // Only re-authenticate when the user explicitly requests it via ?signin=1.
        // Automatic redirects from AuthorizationMiddleware to /sign-in do NOT clear
        // the flag — the user must land on /sign-in and click "Sign in" to trigger this.
        if ($isSignedOut) {
            $params = $request->getQueryParams();
            if (isset($params['signin'])) {
                $session->unset(self::SIGNED_OUT_SESSION_KEY);
            } else {
                return $handler->handle($request);
            }
        }

        $token = $this->cognitoClient->fetchTestToken($this->devEmail);

        return $handler->handle(
            $request->withHeader(self::AWS_COGNITO_HEADER, $token)
        );
    }
}
