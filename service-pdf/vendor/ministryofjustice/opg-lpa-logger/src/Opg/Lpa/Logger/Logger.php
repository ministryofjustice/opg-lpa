<?php
namespace Opg\Lpa\Logger;

use Zend\Log\Logger as ZendLogger;
use Zend\Log\Writer\Stream;
use Opg\Lpa\Logger\Formatter\Logstash;
use Opg\Lpa\Logger\Writer\Sentry;

/**
 * class Logger
 * 
 * A simple logstash file logger
 */
class Logger extends ZendLogger 
{
    private $formatter;
    
    public function __construct()
    {
        parent::__construct();
        $this->formatter = new Logstash();
    }
    
    public function setFileLogPath($logFilename)
    {
        $this->addWriter(
            new Stream($logFilename)
        );
    }
    
    public function setSentryUri($sentryUri)
    {
        $this->addWriter(
            new Sentry($sentryUri)
        );
    }
    
    public function addWriter($logWriter)
    {
        $logWriter->setFormatter($this->formatter);
        parent::addWriter($logWriter);
    } 
}
