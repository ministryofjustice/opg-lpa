<?php

namespace OpgTest\Lpa\Logger;

use Opg\Lpa\Logger\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testMessageLogging()
    {
        $filename = '/tmp/logger-test-' . uniqid() . '.temp';
        
        $message1 = 'Hello world';
        $message2 = 'Hello again';
        $message3 = 'I am warning you';
        
        $logger = new Logger();
        $logger->setFileLogPath($filename);
        
        // Add your sentryApiKey here to test Sentry - don't commit to repo
        // $logger->setSentryUri($sentryApiKey);
        
        $logger->alert($message1);
        $logger->err($message2);
        $logger->warn($message3);
        
        $jsonLines = file($filename);

        $decodedJson = [];
        
        foreach ($jsonLines as $jsonLine) {
            $decodedJson[] = json_decode($jsonLine);
        }
        
        $this->assertEquals($message1, $decodedJson[0]->message);
        $this->assertEquals($message2, $decodedJson[1]->message);
        $this->assertEquals($message3, $decodedJson[2]->message);
        
        $this->assertEquals('ALERT', $decodedJson[0]->priorityName);
        $this->assertEquals('ERR', $decodedJson[1]->priorityName);
    }
}
