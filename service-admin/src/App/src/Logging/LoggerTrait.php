<?php

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
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
            $this->logger = new Logger('MakeAnLPALogger');
            $this->logger->pushHandler(new StreamHandler('php://stderr', Level::Debug));
        }

        return $this->logger;
    }
}
