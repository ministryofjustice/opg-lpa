<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\AbstractIndividualPdf;
use Opg\Lpa\DataModel\Lpa\Lpa;
use ConfigSetUp;
use Opg\Lpa\Pdf\Config\Config;
use PHPUnit\Framework\TestCase;

abstract class AbstractFormTestClass extends TestCase
{
    private $reflectionProperties = [];

    protected function setUp()
    {
        ConfigSetUp::init();

        //  Make some private/protected fields be accessible via reflection
        $pdfProperties = [
            'Opg\Lpa\Pdf\AbstractPdf' => [
                'numberOfPages',
                'formattedLpaRef',
            ],
            'Opg\Lpa\Pdf\AbstractIndividualPdf' => [
                'templateFileName',
                'data',
                'strikeThroughTargets',
                'constituentPdfs',
                'pageShift',
            ],
        ];

        foreach ($pdfProperties as $className => $pdfPropertiesForClass) {
            foreach ($pdfPropertiesForClass as $pdfPropertyForClass) {
                $formReflectionClass = new \ReflectionClass($className);
                $reflectionProperty = $formReflectionClass->getProperty($pdfPropertyForClass);
                $reflectionProperty->setAccessible(true);
                $this->reflectionProperties[$pdfPropertyForClass] = $reflectionProperty;
            }
        }
    }

    protected function getLpa($isPfLpa = true)
    {
        $lpaDataFileName = __DIR__ . '/../../../fixtures/' . ($isPfLpa ? 'lpa-pf.json' : 'lpa-hw.json');

        return new Lpa(file_get_contents($lpaDataFileName));
    }

    protected function getFullTemplatePath($templateName)
    {
        $config = Config::getInstance();

        return $config['service']['assets']['template_path_on_ram_disk'] . '/' . $templateName;
    }

    protected function verifyExpectedPdfData(AbstractIndividualPdf $pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift)
    {
        //  Verify the provided expected data is as expected
        $this->verifyNumberOfPages($pdf, $numberOfPages);
        $this->verifyFormattedLpaRef($pdf, $formattedLpaRef);
        $this->verifyTemplateFileName($pdf, $templateFileName);
        $this->verifyStrikeThroughTargets($pdf, $strikeThroughs);
        $this->verifyConstituentPdfs($pdf, $constituentPdfs);
        $this->verifyData($pdf, $data);
        $this->verifyPageShift($pdf, $pageShift);
    }

    private function verifyNumberOfPages(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('numberOfPages', $form, $expectedValue);
    }

    private function verifyFormattedLpaRef(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('formattedLpaRef', $form, $expectedValue);
    }

    private function verifyTemplateFileName(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('templateFileName', $form, $expectedValue);
    }

    private function verifyData(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('data', $form, $expectedValue);
    }

    private function verifyStrikeThroughTargets(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('strikeThroughTargets', $form, $expectedValue);
    }

    private function verifyConstituentPdfs(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('constituentPdfs', $form, $expectedValue);
    }

    private function verifyPageShift(AbstractIndividualPdf $form, $expectedValue)
    {
        $this->verifyReflectionProperty('pageShift', $form, $expectedValue);
    }

    private function verifyReflectionProperty($propertyName, AbstractIndividualPdf $form, $expectedValue)
    {
        $property = $this->reflectionProperties[$propertyName]->getValue($form);

        $this->assertEquals($expectedValue, $property);
    }

    protected function verifyTmpFileName(Lpa $lpa, $fileName, $templateFileName)
    {
        //  Construct the Regex for the expected filename for this file
        $lpaId = 'A' . str_pad($lpa->id, 11, '0', STR_PAD_LEFT);
        $lpaId = implode('-', str_split($lpaId, 4));
        $regex = '/tmp\/\d{10}.(\d+)?-' . $lpaId . '-' . $templateFileName . '/';

        $this->assertRegExp($regex, $fileName);
    }
}
