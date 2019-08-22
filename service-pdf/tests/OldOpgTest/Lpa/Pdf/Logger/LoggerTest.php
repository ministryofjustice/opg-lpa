<?php

namespace OpgTest\Lpa\Pdf\Logger;

use Opg\Lpa\Pdf\Logger\Logger;
use ConfigSetUp;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function setUp()
    {
        ConfigSetUp::init();
    }

    public function testGetInstance()
    {
        $loggerObj1 = Logger::getInstance();
        $loggerObj2 = Logger::getInstance();

        $this->assertEquals(spl_object_hash($loggerObj1), spl_object_hash($loggerObj2));
    }

    public function tearDown()
    {
        Logger::destroy();
    }
}
