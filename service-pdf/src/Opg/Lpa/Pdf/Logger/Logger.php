<?php

namespace Opg\Lpa\Pdf\Logger;

use Opg\Lpa\Logger\Logger as LpaLogger;
use Opg\Lpa\Pdf\Config\Config;

class Logger extends LpaLogger
{
    private static $instance = null;

    /**
     * Construct this logger with values from config
     */
    public function __construct()
    {
        parent::__construct();

        $logConfig = Config::getInstance()['log'];

        $this->setFileLogPath($logConfig['path']);
        $this->setSentryUri($logConfig['sentry-uri']);
    }

    /**
     * Singleton provider
     *
     * @return \Opg\Lpa\Pdf\Logger\Logger
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}