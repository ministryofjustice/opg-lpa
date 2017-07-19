<?php

namespace OpgTest\Lpa\Pdf\Logger;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        //  Change the logging destination from a physical file to /dev/null while testing
        $loggerConfig = Config::getInstance()['log'];
        $loggerConfig['path'] = '/dev/null';

        Config::getInstance()['log'] = $loggerConfig;
    }

    public function testGetInstance()
    {
        $loggerObj1 = Logger::getInstance();
        $loggerObj2 = Logger::getInstance();

        $this->assertEquals(spl_object_hash($loggerObj1), spl_object_hash($loggerObj2));
    }
}
