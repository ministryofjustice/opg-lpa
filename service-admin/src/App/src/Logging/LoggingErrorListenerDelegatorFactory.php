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
     * @param ContainerInterface $container (unused)
     * @param string $name (unused)
     * @param callable $callback
     * @return ErrorHandler
     */
    public function __invoke(ContainerInterface $_1, string $_2, callable $callback): ErrorHandler
    {
        /** @var ErrorHandler $errorHandler */
        $errorHandler = $callback();
        $errorHandler->attachListener(new LoggingErrorListener());

        return $errorHandler;
    }
}
