<?php

namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Generator;
use Mockery as m;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorAndTempPathCreation()
    {
        //  Mock the LPA and response
        $lpa = m::mock('Opg\Lpa\DataModel\Lpa\Lpa');
        $lpa->shouldReceive('get')
            ->andReturn(12345678);

        $response = m::mock('Opg\Lpa\Pdf\Service\ResponseInterface');

        $generator = new Generator(Generator::TYPE_FORM_LP1, $lpa, $response);

        $this->assertInstanceOf('Opg\Lpa\Pdf\Service\Generator', $generator);

        $config = Config::getInstance();
        $this->assertFileExists($config['service']['assets']['template_path_on_ram_disk']);
    }

    public function tearDown()
    {
        //  Tidy up the temp folder with files
        $config = Config::getInstance();
        $tempFileFolderDestination = $config['service']['assets']['template_path_on_ram_disk'];

        array_map('unlink', glob($tempFileFolderDestination . '/*.*'));
        rmdir($tempFileFolderDestination);
    }
}
