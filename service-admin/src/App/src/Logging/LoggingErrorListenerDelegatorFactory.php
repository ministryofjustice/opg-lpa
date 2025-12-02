<?php

namespace App\Logging;

use Psr\Container\ContainerInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Log\LoggerInterface;

class LoggingErrorListenerDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $_2, callable $callback): ErrorHandler
    {
        /** @var ErrorHandler $errorHandler */
        $errorHandler = $callback();
        $listener     = new LoggingErrorListener();
        $listener->setLogger($container->get(LoggerInterface::class));

        $errorHandler->attachListener($listener);

        return $errorHandler;
    }
}
