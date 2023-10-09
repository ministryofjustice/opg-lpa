<?php

namespace MakeShared\Logging;

use Monolog\Logger as MonologLogger;
use MakeShared\Logging\Logger;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 */
trait LoggerTrait
{
    /**
     * @var MonologLogger
     */
    private $logger;

    /**
     * @param MonologLogger $logger
     * @return $this
     */
    public function setLogger(MonologLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return MonologLogger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof MonologLogger) {
            $this->logger = new Logger();
        }

        return $this->logger;
    }
}
