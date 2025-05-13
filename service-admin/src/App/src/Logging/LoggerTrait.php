<?php

namespace App\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream as StreamWriter;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 * @package App\Logging
 */
trait LoggerTrait
{
    private ?LoggerInterface $logger = null;

    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $logger = new LaminasLogger();
            $logger->addWriter(new StreamWriter('php://stderr'));
            $this->logger = new PsrLoggerAdapter($logger);
        }

        return $this->logger;
    }
}
