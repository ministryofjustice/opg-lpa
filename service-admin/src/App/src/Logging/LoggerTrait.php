<?php

namespace App\Logging;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


/**
 * Trait LoggerTrait
 * @package App\Logging
 */
trait LoggerTrait
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @return Logger $logger
     */
    public function getLogger()
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = new Logger('logger');
            $this->logger->pushHandler(new StreamHandler('php://stderr', Level::Warning));
        }

        return $this->logger;
    }
}
