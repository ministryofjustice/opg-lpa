<?php

namespace OpgTest\Lpa\Logger;

use Opg\Lpa\Logger\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;

class LoggerTest extends TestCase
{
    private $fileLogPath = '/tmp/testlog.log';

    /**
     * @var Logger
     */
    private $logger;

    public function setUp()
    {
        $this->logger = new Logger($this->fileLogPath);
    }

    public function testInfo()
    {
        $this->logger->alert('Alert');
        $logged = $this->getLogLine();
        $this->assertContains('"priority":1,"priorityName":"ALERT","message":"Alert"', $logged);
    }

    public function tearDown()
    {
        unlink($this->fileLogPath);
    }

    /**
     * @return bool|string
     */
    public function getLogLine()
    {
        $line = fgets(fopen($this->fileLogPath, 'r'));
        if ($line !== false) {
            $line = preg_replace('/\r|\n/', '', $line);
        }
        return $line;
    }
}
