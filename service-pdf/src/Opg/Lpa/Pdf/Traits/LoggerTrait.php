<?php

namespace Opg\Lpa\Pdf\Traits;

use MakeShared\Logging\OpgJsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

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
            $this->logger = new Logger('opg-lpa/pdf');

            $streamHandler = new StreamHandler('php://stderr', Level::Debug);
            $streamHandler->setFormatter(new OpgJsonFormatter());

            $this->logger->pushHandler($streamHandler);
        }

        return $this->logger;
    }
}
