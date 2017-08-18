<?php

namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Generator;
use Opg\Lpa\Pdf\Worker\Response\AbstractResponse;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Validator\ValidatorResponseInterface;
use ConfigSetUp;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

class GeneratorTest extends TestCase
{
    public function setUp()
    {
        ConfigSetUp::init();
    }

    public function testConstructorAndTempPathCreation()
    {
        $generator = new Generator(Generator::TYPE_FORM_LP1, $this->getLpa(), $this->getResponse());

        $this->assertInstanceOf(Generator::class, $generator);

        $config = Config::getInstance();
        $this->assertFileExists($config['service']['assets']['template_path_on_ram_disk']);
    }

    public function testGenerateLp1fReturnsTrue()
    {
        $generator = new Generator(Generator::TYPE_FORM_LP1, $this->getLpa(), $this->getResponse());

        $this->assertTrue($generator->generate());
    }

    public function testGenerateLp1hReturnsTrue()
    {
        $generator = new Generator(Generator::TYPE_FORM_LP1, $this->getLpa(false), $this->getResponse());

        $this->assertTrue($generator->generate());
    }

    public function testGenerateLpaFailsValidation()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA failed validation');

        $generator = new Generator(Generator::TYPE_FORM_LP1, $this->getLpa(true, false), $this->getResponse());

        $generator->generate();
    }

    public function testGenerateLp1CannotGenerate()
    {
        //  Set the instructions and preferences to null so that the can generate test fails
        $lpa = $this->getLpa();
        $lpa->document->instruction = null;
        $lpa->document->preference = null;

        $generator = new Generator(Generator::TYPE_FORM_LP1, $lpa, $this->getResponse());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate a LP1');

        $generator->generate();
    }

    public function testGenerateLp3ReturnsTrue()
    {
        $generator = new Generator(Generator::TYPE_FORM_LP3, $this->getLpa(), $this->getResponse());

        $this->assertTrue($generator->generate());
    }

    public function testGenerateLp3CannotGenerate()
    {
        //  Set the instructions and preferences to null so that the can generate test fails
        $lpa = $this->getLpa();
        $lpa->document->peopleToNotify = [];

        $generator = new Generator(Generator::TYPE_FORM_LP3, $lpa, $this->getResponse());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate a LP3');

        $generator->generate();
    }

    public function testGenerateLp120ReturnsTrue()
    {
        $generator = new Generator(Generator::TYPE_FORM_LPA120, $this->getLpa(), $this->getResponse());

        $this->assertTrue($generator->generate());
    }

    public function testGenerateLpa120CannotGenerate()
    {
        //  Set the instructions and preferences to null so that the can generate test fails
        $lpa = $this->getLpa();
        $lpa->payment = null;

        $generator = new Generator(Generator::TYPE_FORM_LPA120, $lpa, $this->getResponse());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LPA does not contain all the required data to generate a LPA120');

        $generator->generate();
    }

    public function testGenerateExceptionUnknownFormType()
    {
        $generator = new Generator('LPA999', $this->getLpa(), $this->getResponse());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid form type: LPA999');

        $generator->generate();
    }

    private function getLpa($isPfLpa = true, $isValid = true)
    {
        $validationResponse = Mockery::mock(ValidatorResponseInterface::class);
        $validationResponse->shouldReceive('hasErrors')
                           ->andReturn(!$isValid);

        //  Mock the LPA
        $lpaDataFile = ($isPfLpa ? 'lpa-pf.json' : 'lpa-hw.json');
        $lpaData = file_get_contents(__DIR__ . '/../../../../fixtures/' . $lpaDataFile);

        $lpa = Mockery::mock(Lpa::class . '[validate]', [$lpaData]);
        $lpa->shouldReceive('validate')
            ->andReturn($validationResponse);

        return $lpa;
    }

    private function getResponse()
    {
        //  Mock the response
        $response = Mockery::mock(AbstractResponse::class);
        $response->shouldReceive('save')
                 ->andReturnNull();

        return $response;
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
