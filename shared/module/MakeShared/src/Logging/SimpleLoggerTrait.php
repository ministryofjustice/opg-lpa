<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;

/**
 * Simplified trait for logging in applications which don't use laminas-mvc.
 * Do not use in classes which already use LoggerTrait from this package.
 *
 * @package MakeShared\Logging
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
            $this->logger = new LaminasLogger();
            $this->logger->addWriter(new StreamWriter('php://stderr'));
        }

        return $this->logger;
    }
}
