<?php

namespace MakeShared\Logging;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 */
trait LoggerTrait
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @return $this
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return Logger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = new Logger();
        }

        return $this->logger;
    }
}
