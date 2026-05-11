<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RequestLoggingMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): RequestLoggingMiddleware
    {
        return new RequestLoggingMiddleware(
            $container->get(LoggerInterface::class)
        );
    }
}
