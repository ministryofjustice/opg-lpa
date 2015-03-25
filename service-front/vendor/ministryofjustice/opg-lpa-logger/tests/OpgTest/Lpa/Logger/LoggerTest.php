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
        
        // Create sentry.key file in /tests to test sending to Sentry
        // It should be ignored by .gitignore
        if (file_exists('sentry.key')) {
            $sentryKey = file_get_contents('sentry.key');
            $logger->setSentryUri($sentryKey);
        }
        
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
