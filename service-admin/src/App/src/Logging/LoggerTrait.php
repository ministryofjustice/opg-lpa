<?php

namespace App\Logging;

use MakeShared\Logging\OpgJsonFormatter;
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
            $this->logger = new Logger('opg-lpa/admin');

            $streamHandler = new StreamHandler('php://stderr', Level::Debug);
            $streamHandler->setFormatter(new OpgJsonFormatter());

            $this->logger->pushHandler($streamHandler);
        }

        return $this->logger;
    }
}
