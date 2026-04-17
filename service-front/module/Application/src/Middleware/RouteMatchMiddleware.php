<?php

declare(strict_types=1);

namespace Application\Middleware;

use Laminas\Router\RouteMatch;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bridges the laminas-mvc RouteMatch (placed on the PSR-7 request by
 * laminas-mvc-middleware's MiddlewareController) into a Mezzio RouteResult.
 *
 * This lets every downstream middleware use the standard Mezzio
 * RouteResult::class attribute regardless of whether it is running inside
 * a laminas-mvc PipeSpec or a full Mezzio pipeline, making the transition
 * transparent.
 *
 * Route options (e.g. `unauthenticated_route`) are carried over from the
 * RouteMatch params so that middleware that inspect them via
 * $route->getMatchedRoute()->getOptions() continue to work. The matched
 * route name is also set as RequestAttribute::CURRENT_ROUTE_NAME on the request
 * for downstream handlers to consume.
 *
 * This is the PSR-7 equivalent of CurrentRouteListener.
 */
class RouteMatchMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);

        if (!$routeMatch instanceof RouteMatch) {
            return $handler->handle($request);
        }

        if ($request->getAttribute(RouteResult::class) instanceof RouteResult) {
            return $handler->handle($request);
        }

        $routeName = $routeMatch->getMatchedRouteName() ?? '';
        $params    = $routeMatch->getParams();

        // Pull well-known "option" keys out of the flat params array so they
        // are accessible via RouteResult->getMatchedRoute()->getOptions(),
        // matching the Mezzio convention used by AuthenticationMiddleware, etc.
        $optionKeys = ['unauthenticated_route', 'allowIncompleteUser'];
        $options    = [];
        foreach ($optionKeys as $key) {
            if (array_key_exists($key, $params)) {
                $options[$key] = $params[$key];
            }
        }

        $route = new Route($routeName !== '' ? $routeName : '/', new StubMiddleware(), null, $routeName);
        $route->setOptions($options);

        $routeResult = RouteResult::fromRoute($route, $params);

        $request = $request
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, $routeName);

        return $handler->handle($request);
    }
}
