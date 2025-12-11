<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\AbstractIndividualPdf;
use MakeShared\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Aggregator\AbstractAggregator;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\PdftkFactory;
use OpgTest\ConfigSetUp;
use OpgTest\Lpa\Pdf\Helper\PdfCompare;
use PHPUnit\Framework\TestCase;

abstract class AbstractPdfTestCase extends TestCase
{
    private $reflectionProperties = [];

    private const FIXTURES_DIR = __DIR__ . '/../../../fixtures/';
    private const BUILD_DIR = __DIR__ . '/../../../../build/';

    protected $formattedLpaRef = 'A510 7295 5715';
    protected $strikeThroughTargets = [];
    protected $blankTargets = [];

    /**
     * PdftkFactory $factory Factory for creating PDF objects within tests;
     * this allows a tester to use a different pdftk command by
     * setting the PDFTK_COMMAND variable in the environment.
     */
    protected $factory;

    protected function setUp(): void
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
                'blankTargets',
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

        $pdftkCommand = $_ENV['PDFTK_COMMAND'] ?? 'pdftk';
        $this->factory = new PdftkFactory($pdftkCommand);
    }

    /**
     * Get the path to the temporary build directory and make it if it doesn't exist
     */
    protected function getBuildDirectory()
    {
        if (!file_exists(self::BUILD_DIR)) {
            mkdir(self::BUILD_DIR);
        }

        return self::BUILD_DIR;
    }

    /**
     * Can be useful for viewing output PDFs after test runs. Although
     * not directly used in tests (to prevent spewing PDFs everywhere), we
     * should retain this for the occasions when we want to view output PDFs.
     *
     * @param string $filename Path to file to be copied
     * @param string $newfilename New file name for copied file; if not set,
     * defaults to basename of $filename
     * @return string
     */
    protected function copyFileToBuildDirectory(string $filename, ?string $newfilename = null)
    {
        if (is_null($newfilename)) {
            $newfilename = basename($filename);
        }
        $destFilename = $this->getBuildDirectory() . $newfilename;
        copy($filename, $destFilename);
        return $destFilename;
    }

    // returns assoc array
    protected function getPfLpaJSON()
    {
        return json_decode(file_get_contents(self::FIXTURES_DIR . 'lpa-pf.json'), true);
    }

    // returns assoc array
    protected function getHwLpaJSON()
    {
        return json_decode(file_get_contents(self::FIXTURES_DIR . 'lpa-hw.json'), true);
    }

    // load assoc array from JSON and return an LPA
    protected function buildLpaFromJSON($data)
    {
        return new Lpa(json_encode($data));
    }

    protected function getLpa($isPfLpa = true)
    {
        $lpaDataFileName = self::FIXTURES_DIR . ($isPfLpa ? 'lpa-pf.json' : 'lpa-hw.json');
        return new Lpa(file_get_contents($lpaDataFileName));
    }

    protected function getFullTemplatePath($templateName)
    {
        $config = Config::getInstance();
        return $config['service']['assets']['template_path'] . '/' . $templateName;
    }

    protected function verifyExpectedPdfData(AbstractIndividualPdf $pdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, $pageShift, $formattedLpaRef = null)
    {
        //  Verify the provided expected data is as expected
        $this->verifyTemplateFileName($pdf, $templateFileName);
        $this->verifyStrikeThroughTargets($pdf, $strikeThroughTargets);
        $this->verifyBlankTargets($pdf, $blankTargets);
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

    private function verifyBlankTargets(AbstractIndividualPdf $pdf, $expectedValue)
    {
        $this->verifyReflectionProperty('blankTargets', $pdf, $expectedValue);
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
                    $blankTargets = $expectedPdf['blankTargets'] ?? [];
                    $constituentPdfs = $expectedPdf['constituentPdfs'] ?? [];
                    $data = $expectedPdf['data'] ?? [];

                    $this->verifyExpectedPdfData($constituentPdf, $templateFileName, $strikeThroughTargets, $blankTargets, $constituentPdfs, $data, 0);
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

    protected function verifyTmpFileName(Lpa $lpa, $fileName, $templateFileName)
    {
        //  Construct the Regex for the expected filename for this file
        $lpaId = 'A' . str_pad($lpa->id, 11, '0', STR_PAD_LEFT);
        $lpaId = implode('-', str_split($lpaId, 4));
        $regex = '/tmp\/\d{10}.(\d+)?-' . $lpaId . '-' . $templateFileName . '/';

        $this->assertMatchesRegularExpression($regex, $fileName);
    }

    protected function visualDiffCheck($pdf, $testFileName)
    {
        $pdfCompare = new PdfCompare();
        $pdfCompare->compare($pdf->getPdfFile(), $testFileName, $pdf->getNumberOfPages());
    }

    private function verifyReflectionProperty($propertyName, AbstractIndividualPdf $pdf, $expectedValue)
    {
        $property = $this->getReflectionPropertyValue($propertyName, $pdf);
        $this->assertEquals($expectedValue, $property, "Property $propertyName did not have the expected value");
    }

    protected function getReflectionPropertyValue($propertyName, AbstractIndividualPdf $pdf)
    {
        return $this->reflectionProperties[$propertyName]->getValue($pdf);
    }

    /**
     * Assertion which passes if each key specified in $possibleSubset
     * occurs in $set, and the values for the keys in the two arrays match
     * per assertEquals().
     *
     * @param array $possibleSubArray Associative array - the subset of the
     * $array to check
     * @param array $array Associative array
     */
    protected function assertArrayIsSubArrayOf($possibleSubArray, $array)
    {
        foreach ($possibleSubArray as $subArrayKey => $subArrayValue) {
            $this->assertEquals($array[$subArrayKey], $subArrayValue);
        }
    }
}
