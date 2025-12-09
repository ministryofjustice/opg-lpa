<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use MakeShared\Constants;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

// For use in Mezzio middleware only
class LoggerRequestContextMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface|Logger $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $this->logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['request_path'] = $request->getUri()->getPath();
            $record['extra']['request_method'] = $request->getMethod();
            $record['extra'][Constants::TRACE_ID_FIELD_NAME] = $request->getHeaderLine('X-Request-ID');
            return $record;
        });

        return $handler->handle($request);
    }
}
