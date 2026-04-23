<?php

declare(strict_types=1);

namespace Application\Middleware;

use MakeShared\Constants;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Pushes a Monolog processor that adds request context (path, method,
 * trace ID) to every log record produced during the request lifecycle.
 *
 * Replicates the logger->pushProcessor() call in Module::onBootstrap(),
 * converted to PSR-15 middleware.
 *
 * Should run early in the pipeline — after ErrorHandler but before any
 * middleware that may log.
 */
class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->logger instanceof Logger) {
            $this->logger->pushProcessor(function (array $record) use ($request): array {
                $record['extra']['request_path']   = $request->getUri()->getPath();
                $record['extra']['request_method'] = $request->getMethod();

                $traceId = $request->getHeaderLine('X-Request-ID');
                if ($traceId !== '') {
                    $record['extra'][Constants::TRACE_ID_FIELD_NAME] = $traceId;
                }

                return $record;
            });
        }

        return $handler->handle($request);
    }
}
