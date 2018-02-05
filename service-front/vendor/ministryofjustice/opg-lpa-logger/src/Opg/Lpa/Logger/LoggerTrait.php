<?php

namespace Opg\Lpa\Logger;

/**
 * Trait LoggerTrait
 * @package Application\Log
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
    protected function getLogger()
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = Logger::getInstance();
        }

        return $this->logger;
    }
}
