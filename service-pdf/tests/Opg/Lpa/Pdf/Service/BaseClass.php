<?php
namespace OpgTest\Lpa\Pdf\Service;

use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\PdftkInstance;
use Opg\Lpa\Pdf\Service\Forms\Lp1h;
use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\Pdf\Service\Forms\Lpa120;

class BaseClass extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var Lpa $lpa
     */
    protected $lpa;
    
    /**
     * File path of generate PDF
     * @var str
     */
    protected $filePath;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        $this->lpa = new Lpa(
                file_get_contents(
                        __DIR__.'/fixtures/base-lpa.json'));
        
        $config = Config::getInstance();
        
        $mockPdftkInstance = $this->getMockBuilder('mikehaertl\pdftk\Pdf')
            ->setMethods(['flatten'])
            ->getMock();
        
        $mockPdftkInstance->expects($this->any())
            ->method('flatten')
            ->will($this->returnSelf());
        
        PdftkInstance::setPdftkInstance($mockPdftkInstance);
        
    }
    
    protected function getFormDataFromPdf($type)
    {
        // generate PDF
        switch($type) {
            case "LP1":
                if($this->lpa->document->type === "property-and-financial") {
                    $pdf = new Lp1f($this->lpa);
                }
                elseif($this->lpa->document->type === "health-and-welfare") {
                    $pdf = new Lp1h($this->lpa);
                }
                break;
            case "LP3":
                $pdf = new Lp3($this->lpa);
                break;
            case "LPA120":
                $pdf = new Lpa120($this->lpa);
                break;
        }
        
        if($pdf->generate()) {
            $filePath = $pdf->getPdfFilePath();
        }
        else {
            $this->fail('Failed generating PDF');
        }
        
        // retrieve form data from Generated PDF
        $newPdf = new Pdf($filePath);
        
        $dataFields = $newPdf->getDataFields(false);
        
        $lines = explode("\n", $dataFields);
        
        $formData = [];
        foreach($lines as $lines) {
        
            $kv = explode(": ", $lines);
            if(sizeof($kv) == 1) {
                continue;
            }
            else {
                if($kv[0] == 'FieldName') {
                    $key = $kv[1];
                }elseif ($kv[0] == 'FieldValue') {
                    $formData[$key] = $kv[1];
                }
            }
        }

        return $formData;
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        parent::tearDown();
    }
}