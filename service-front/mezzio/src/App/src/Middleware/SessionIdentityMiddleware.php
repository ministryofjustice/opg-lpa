<?php

declare(strict_types=1);

namespace App\Middleware;

use Application\Middleware\RequestAttribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use DateTime;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reads the authenticated identity from the Mezzio session and:
 *   1. Writes it into the LPA auth service's NonPersistent storage so that
 *      AbstractService::getUserId() returns the correct value for the request.
 *   2. Sets it as RequestAttribute::IDENTITY on the request for downstream
 *      middleware and handlers.
 *
 * Must run after Mezzio\Session\SessionMiddleware.
 */
class SessionIdentityMiddleware implements MiddlewareInterface
{
    private const string SESSION_KEY = 'identity';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if ($session instanceof SessionInterface && $session->has(self::SESSION_KEY)) {
            $data = $session->get(self::SESSION_KEY);

            if (is_array($data) && isset($data['userId'], $data['token'], $data['tokenExpiresAt'])) {
                $tokenExpiresAt = new DateTime($data['tokenExpiresAt']);
                $expiresIn      = max(0, $tokenExpiresAt->getTimestamp() - time());

                $lastLogin = isset($data['lastLogin'])
                    ? new DateTime($data['lastLogin'])
                    : null;

                $identity = new User(
                    $data['userId'],
                    $data['token'],
                    $expiresIn,
                    $lastLogin,
                );

                // Write the identity into the auth service storage so that
                // AbstractService::getUserId() returns the correct value.
                $this->authenticationService->getStorage()->write($identity);

                $request = $request->withAttribute(RequestAttribute::IDENTITY, $identity);
            }
        }

        return $handler->handle($request);
    }
}
