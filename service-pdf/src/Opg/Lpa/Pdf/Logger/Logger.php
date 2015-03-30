<?php
namespace Opg\Lpa\Pdf\Logger;

use Opg\Lpa\Logger\Logger as LpaLogger;
use Opg\Lpa\Pdf\Config\Config;

class Logger extends LpaLogger
{
    static $instance = null;
    
    public function __construct()
    {
        parent::__construct();
        
        $logConfig = Config::getInstance()['log'];

        $this->setFileLogPath($logConfig['path']);
        $this->setSentryUri($logConfig['sentry-uri']);
    }
    
    static public function getInstance( )
    {
        if(self::$instance === null) {
            self::$instance = new self( );
        }
    
        return self::$instance;
    }
}
