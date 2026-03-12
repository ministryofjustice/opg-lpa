<?php

declare(strict_types=1);

namespace Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// We need a MiddlewareInterface stub for RouteMatchMiddleware
// as Mezzio\Router\Route requires one, but it is never invoked
// through this RouteMatchMiddleware – the real handler is already in
// the pipeline.
class StubMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }
}
