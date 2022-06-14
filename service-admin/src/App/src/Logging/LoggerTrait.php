<?php

namespace App\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;

/**
 * Trait LoggerTrait
 * @package App\Logging
 */
trait LoggerTrait
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
