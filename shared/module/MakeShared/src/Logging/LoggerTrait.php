<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use MakeShared\Logging\Logger;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 */
trait LoggerTrait
{
    /**
     * @var LaminasLogger
     */
    private $logger;

    /**
     * @param LaminasLogger $logger
     * @return $this
     */
    public function setLogger(LaminasLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LaminasLogger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof LaminasLogger) {
            $this->logger = new Logger();
        }

        return $this->logger;
    }
}
