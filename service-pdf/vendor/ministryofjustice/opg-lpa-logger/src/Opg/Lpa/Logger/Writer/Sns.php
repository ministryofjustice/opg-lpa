<?php
namespace Opg\Lpa\Logger\Writer;

use Zend\Log\Writer\AbstractWriter;
use Aws\Sns\SnsClient;

class Sns extends AbstractWriter
{
    const SNS_IGNORE = 1;
    const SNS_MINOR = 2;
    const SNS_MAJOR = 3;
    
    /**
     * Translates Zend Framework log levels to minor or major
     */
    private $logLevels = [
        'DEBUG'     => self::SNS_IGNORE,
        'INFO'      => self::SNS_IGNORE,
        'NOTICE'    => self::SNS_IGNORE,
        'WARN'      => self::SNS_IGNORE,
        'ERR'       => self::SNS_IGNORE,
        'CRIT'      => self::SNS_MINOR,
        'ALERT'     => self::SNS_MINOR,
        'EMERG'     => self::SNS_MAJOR,
    ];
    
    /**
     * @var Aws\Sns\SnsClient
     */
    private $snsClient;
    
    /**
     * @var array
     */
    private $endpoints;
    
    /**
     * Constructor
     *
     * @return array $options
     */
    public function __construct(array $config, array $endpoints, $options = null)
    {
        $this->snsClient = new SnsClient($config);
        
        $this->endpoints = $endpoints;
        
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
        
        $snsLogLevel = $this->logLevels[$zendLogLevel];
        
        if ($snsLogLevel != self::SNS_IGNORE) {
            $this->sendSnsNotification(
                $this->endpoints[$snsLogLevel],
                $event,
                $zendLogLevel
            );
        }
    }
    
    /**
     * Send the SNS notification
     * 
     * @param string $endpoint
     * @param array $event
     * @param string $zendLogLevel
     */
    private function sendSnsNotification($endpoint, $event, $zendLogLevel) {
        
        $result = $this->snsClient->publish(array(
            'TopicArn' => $endpoint,
            'Message' => $event['message'],
            'Subject' => 'OPG LPA Digital Service Alert',
            'MessageStructure' => 'string',
            'MessageAttributes' => array(
                'ZendLogLevel' => array(
                    'DataType' => 'String',
                    'StringValue' => $zendLogLevel,
                ),
            ),
        ));
    }
}