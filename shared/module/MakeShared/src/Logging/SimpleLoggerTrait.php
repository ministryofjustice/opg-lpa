<?php

namespace MakeShared\Logging;

use Monolog\Logger as MonologLogger;
use MakeShared\Logging\SimpleLogger;

/**
 * Simplified trait for logging in applications which don't use laminas-mvc.
 * Do not use in classes which already use LoggerTrait from this package.
 */
trait SimpleLoggerTrait
{
    /**
     * @var MonologLogger
     */
    private $logger;

    /**
     * @return MonologLogger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof MonologLogger) {
            $this->logger = new SimpleLogger();
        }

        return $this->logger;
    }
}
