<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

// For use in Mezzio middleware only
class LoggerRequestContextMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LoggerRequestContextMiddleware
    {
        return new LoggerRequestContextMiddleware(
            $container->get(LoggerInterface::class)
        );
    }
}
