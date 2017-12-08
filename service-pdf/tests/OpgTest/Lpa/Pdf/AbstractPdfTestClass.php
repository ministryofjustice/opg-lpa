<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\AbstractIndividualPdf;
use Opg\Lpa\DataModel\Lpa\Lpa;
use ConfigSetUp;
use Opg\Lpa\Pdf\Aggregator\AbstractAggregator;
use Opg\Lpa\Pdf\Config\Config;
use PHPUnit\Framework\TestCase;

abstract class AbstractPdfTestClass extends TestCase
{
    private $reflectionProperties = [];

    protected function setUp()
    {
        ConfigSetUp::init();

        //  Make some private/protected fields be accessible via reflection
        $pdfProperties = [
            'Opg\Lpa\Pdf\AbstractPdf' => [
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

    protected function verifyExpectedPdfData(AbstractIndividualPdf $pdf, $templateFileName, $strikeThroughTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef = null)
    {
        //  Verify the provided expected data is as expected
        $this->verifyTemplateFileName($pdf, $templateFileName);
        $this->verifyStrikeThroughTargets($pdf, $strikeThroughTargets);
        $this->verifyConstituentPdfs($pdf, $constituentPdfs);
        $this->verifyData($pdf, $data);
        $this->verifyPageShift($pdf, $pageShift);

        if (!is_null($formattedLpaRef)) {
            $this->verifyFormattedLpaRef($pdf, $formattedLpaRef);
        }
    }

    private function verifyFormattedLpaRef(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('formattedLpaRef', $pdf, $expectedValue);
    }

    private function verifyTemplateFileName(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('templateFileName', $pdf, $expectedValue);
    }

    private function verifyData(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('data', $pdf, $expectedValue);
    }

    private function verifyStrikeThroughTargets(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('strikeThroughTargets', $pdf, $expectedValue);
    }

    private function verifyConstituentPdfs(AbstractIndividualPdf $pdf, $expectedValue)
    {
        //  First loop through the reflection values and swap out the instances where the pdf set is actually a PDF object
        //  We do not need to test that here
        $constituentPdfsForPages = $this->reflectionProperties['constituentPdfs']->getValue($pdf);

        foreach ($constituentPdfsForPages as $page => $constituentPdfsForPage) {
            foreach ($constituentPdfsForPage as $constituentPdfIdx => $constituentPdfForPage) {
                //  Confirm that there is a corresponding entry in the expected values
                if (!isset($expectedValue[$page]) || !array_key_exists($constituentPdfIdx, $expectedValue[$page])) {
                    $this->fail(sprintf('Expected value missing while trying to verify constituent PDF index %s inserted after page %s', $constituentPdfIdx, $page));
                }

                $expectedPdf = $expectedValue[$page][$constituentPdfIdx];
                $constituentPdf = $constituentPdfForPage['pdf'];

                if ($constituentPdf instanceof AbstractIndividualPdf) {
                    //  Confirm that the provided expected value is an array
                    if (!is_array($expectedPdf)) {
                        $this->fail(sprintf('Expected value is not an array of data while trying to verify constituent PDF index %s inserted after page %s', $constituentPdfIdx, $page));
                    }

                    $templateFileName = $expectedPdf['templateFileName'];
                    $strikeThroughTargets = $expectedPdf['strikeThroughTargets'] ?? [];
                    $constituentPdfs = $expectedPdf['constituentPdfs'] ?? [];
                    $data = $expectedPdf['data'] ?? [];

                    $this->verifyExpectedPdfData($constituentPdf, $templateFileName, $strikeThroughTargets, $constituentPdfs, $data, 0);
                } elseif ($constituentPdf instanceof AbstractAggregator) {
                    //  TODO - How to test aggregators here? Make the PDFs protected property in there be visible too and feed back in?
                    //  For now just assert that the expected value is null as a placeholder
                    if (!is_null($expectedPdf)) {
                        $this->fail('Non null constituent PDF config for aggregator at ' . $page . '-' . $constituentPdfIdx);
                    }
                } else {
                    //  The value should be a string so do a direct comparison
                    $this->assertEquals($expectedPdf, $constituentPdf);
                }
            }
        }
    }

    private function verifyPageShift(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('pageShift', $pdf, $expectedValue);
    }

    private function verifyReflectionProperty($propertyName, AbstractIndividualPdf $pdf, $expectedValue)
    {
        $property = $this->reflectionProperties[$propertyName]->getValue($pdf);

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
