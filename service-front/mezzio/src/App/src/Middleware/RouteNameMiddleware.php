<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Extracts the matched route name from the Mezzio RouteResult and sets it as
 * RequestAttribute::CURRENT_ROUTE_NAME on the request.
 *
 * Root-cause context
 * ------------------
 * In Laminas MVC, controllers had implicit access to the matched route name via
 *   $this->getEvent()->getRouteMatch()->getMatchedRouteName()
 * In Mezzio, the route name is available via
 *   $request->getAttribute(RouteResult::class)->getMatchedRouteName()
 * but only after RouteMiddleware has run.  Rather than require every handler to
 * reach into RouteResult directly (changing 31+ handler call-sites), this
 * middleware runs immediately after RouteMiddleware and propagates the value onto
 * the request under the well-known RequestAttribute::CURRENT_ROUTE_NAME key —
 * giving handlers the same implicit access they had under MVC.
 */
class RouteNameMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);

        if ($routeResult instanceof RouteResult && !$routeResult->isFailure()) {
            $request = $request->withAttribute(
                RequestAttribute::CURRENT_ROUTE_NAME,
                $routeResult->getMatchedRouteName() ?? '',
            );
        }

        return $handler->handle($request);
    }
}
