<?php

namespace MakeShared\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 */
trait LoggerTrait
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = new \Monolog\Logger('MakeAnLPALogger');
            $this->logger->pushHandler(new StreamHandler('php://stderr', Level::Debug  ));
        }
        return $this->logger;
    }
}
