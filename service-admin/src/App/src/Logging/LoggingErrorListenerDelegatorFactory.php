<?php

namespace App\Logging;

use Psr\Container\ContainerInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

/**
 * Class LoggingErrorListenerDelegatorFactory
 * @package App\Logging
 */
class LoggingErrorListenerDelegatorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @return ErrorHandler
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ErrorHandler
    {
        /** @var ErrorHandler $errorHandler */
        $errorHandler = $callback();
        $errorHandler->attachListener(new LoggingErrorListener());

        return $errorHandler;
    }
}
