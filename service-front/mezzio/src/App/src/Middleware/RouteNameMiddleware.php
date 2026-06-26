<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
