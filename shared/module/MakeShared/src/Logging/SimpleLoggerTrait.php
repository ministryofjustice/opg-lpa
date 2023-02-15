<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use MakeShared\Logging\SimpleLogger;

/**
 * Simplified trait for logging in applications which don't use laminas-mvc.
 * Do not use in classes which already use LoggerTrait from this package.
 */
trait SimpleLoggerTrait
{
    /**
     * @var LaminasLogger
     */
    private $logger;

    /**
     * @return LaminasLogger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof LaminasLogger) {
            $this->logger = new SimpleLogger();
        }

        return $this->logger;
    }
}
