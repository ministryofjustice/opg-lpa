<?php
namespace Opg\Lpa\Logger\Writer;

use Zend\Log\Writer\AbstractWriter;

use Raven_Client as Raven;

class Sentry extends AbstractWriter
{
    /**
     * Translates Zend Framework log levels to Raven log levels.
     */
    private $logLevels = [
        'DEBUG'     => Raven::DEBUG,
        'INFO'      => Raven::INFO,
        'NOTICE'    => Raven::INFO,
        'WARN'      => Raven::WARNING,
        'ERR'       => Raven::ERROR,
        'CRIT'      => Raven::FATAL,
        'ALERT'     => Raven::FATAL,
        'EMERG'     => Raven::FATAL,
    ];
    
    /**
     * Don't send messages with these log levels to Sentry
     */
    private $ignoreLevels = [
        'DEBUG',
        'INFO',
        'NOTICE',
        'WARN',
    ];
    
    protected $raven;
    
    /**
     * Constructor
     *
     * @param string $sentryApiKey
     * @return array $options
     */
    public function __construct($sentryApiKey, $options = null)
    {
        $this->raven = new Raven($sentryApiKey);
        parent::__construct($options);
    }
    
    /**
     * Write a message to the log
     *
     * @param array $event log data event
     * @return number The event ID
     */
    protected function doWrite(array $event)
    {
        $extra = array();
        $extra['timestamp'] = $event['timestamp'];
        
        $zendLogLevel = $event['priorityName'];
        
        if (!in_array($zendLogLevel, $this->ignoreLevels)) {
            $sentryLogLevel = $this->logLevels[$zendLogLevel];
            return $this->raven->captureMessage(
                $event['message'], 
                [],
                $sentryLogLevel,
                false,
                $event['extra']
            );
        }
    }
}