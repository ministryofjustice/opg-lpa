<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use MakeShared\Constants;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface|Logger $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->logger instanceof Logger) {
            $this->logger->pushProcessor(function (LogRecord $record) use ($request): LogRecord {
                $traceId = $request->getHeaderLine('X-Trace-Id') ?: $request->getHeaderLine('X-Request-ID');

                if ($traceId === '') {
                    $traceId = 'not available';
                }

                return $record->with(extra: array_merge($record->extra, [
                    'request_path'                 => $request->getUri()->getPath(),
                    'request_method'               => $request->getMethod(),
                    Constants::TRACE_ID_FIELD_NAME => $traceId,
                ]));
            });
        }

        return $handler->handle($request);
    }
}
