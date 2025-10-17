<?php

namespace Opg\Lpa\Pdf\Traits;

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
            $this->logger = new Logger('MakeAnLPALogger');
            $this->logger->pushHandler(new StreamHandler('php://stderr', Level::Debug));
        }

        return $this->logger;
    }
}
