<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream as StreamWriter;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 */
trait LoggerTrait
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

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
