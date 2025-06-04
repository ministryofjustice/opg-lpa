<?php

namespace MakeShared\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 * @package MakeShared\Logging
 * @psalm-require-implements LoggerAwareInterface
 */
trait LoggerTrait
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new \RuntimeException('Logger not set');
        }
        return $this->logger;
    }
}
