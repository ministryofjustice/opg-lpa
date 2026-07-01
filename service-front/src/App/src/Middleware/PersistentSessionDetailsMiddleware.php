<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\Service\Session\PersistentSessionDetails;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Mezzio-specific middleware with no direct MVC equivalent.
 *
 * In the legacy MVC app, PersistentSessionDetails was created per-request via a service manager
 * factory in Module.php, which had access to the current RouteMatch at creation time.
 *
 * In Mezzio, services are shared container singletons, so this middleware is needed to inject
 * the per-request RouteResult and SessionInterface into the shared PersistentSessionDetails
 * instance on each request. It must run after RouteMiddleware so that RouteResult is available.
 */
class PersistentSessionDetailsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly PersistentSessionDetails $persistentSessionDetails)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $this->persistentSessionDetails->refresh($routeResult, $session);

        return $handler->handle($request);
    }
}
