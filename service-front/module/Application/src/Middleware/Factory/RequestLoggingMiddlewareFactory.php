<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\RequestLoggingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RequestLoggingMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): RequestLoggingMiddleware
    {
        return new RequestLoggingMiddleware($container->get(LoggerInterface::class));
    }
}
