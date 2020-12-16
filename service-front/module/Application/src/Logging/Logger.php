<?php

namespace Application\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;
use Laminas\Log\Formatter\Json as JsonFormatter;

use Application\Logging\EventProcessor;

/**
 * class Logger
 *
 * A simple StreamWriter file logger which converts log events to JSON.
 */
class Logger extends LaminasLogger
{
    /**
     * @var Logger
     */
    private static $instance = null;

    /**
     * Logger constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->addProcessor(new EventProcessor());

        $writer = new StreamWriter('php://stderr');
        $writer->setFormatter(new JsonFormatter());

        $this->addWriter($writer);
    }

    /**
     * Singleton provider for logger
     * Required so logger can be loaded in all services including none
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Destroy the logger
     */
    public static function destroy()
    {
        self::$instance = null;
    }
}
