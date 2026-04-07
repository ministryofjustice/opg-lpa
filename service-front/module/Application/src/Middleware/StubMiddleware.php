<?php

declare(strict_types=1);

namespace Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A no-op middleware required solely to satisfy the Mezzio\Router\Route constructor,
 * which demands a MiddlewareInterface instance. RouteMatchMiddleware constructs a
 * Route object in order to produce a RouteResult, but the Route's middleware is never
 * invoked — the real handler is already in the PipeSpec pipeline ahead of it.
 */
class StubMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }
}
