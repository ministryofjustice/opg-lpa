<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use App\Storage\MezzioSessionStorage;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Mezzio port of the legacy SessionExpiryHandler.
 *
 * Calls the API /v2/session-expiry endpoint using the current user's token.
 * Returns JSON { remainingSeconds: N } if the session is still valid,
 * or 204 (and clears the session) if it has expired.
 */
class SessionExpiryHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaAuthAdapter $authAdapter,
        private readonly MezzioSessionStorage $sessionStorage,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->sessionStorage->read();

        if ($identity === null) {
            return new EmptyResponse(204);
        }

        $result = $this->authAdapter->getSessionExpiry($identity->token());

        if ($result === null || !isset($result['valid']) || !$result['valid']) {
            // Session has expired — clear it
            $this->sessionStorage->clear();
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            if ($session instanceof SessionInterface) {
                $session->clear();
                $session->regenerate();
            }

            return new EmptyResponse(204);
        }

        return new JsonResponse(['remainingSeconds' => $result['remainingSeconds']]);
    }
}
