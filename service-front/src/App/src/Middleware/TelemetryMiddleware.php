<?php

declare(strict_types=1);

namespace App\Middleware;

use MakeShared\Telemetry\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A no-op middleware required solely to satisfy the Mezzio\Router\Route constructor,
 * which demands a MiddlewareInterface instance. RouteResult constructs a Route object
 * in order to produce a RouteResult, but the Route's middleware is never invoked.
 */
class TelemetryMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Tracer $tracer)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->tracer->startRootSegment();

        try {
            return $handler->handle($request);
        } finally {
            $this->tracer->stopRootSegment();
        }
    }
}
