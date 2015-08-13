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
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\Pdf\Service\Forms\Lp1;

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
    
    protected function extractFormDataFromPdf($type, $savePath=null)
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
        
        if($savePath) {
            copy ($filePath, $savePath.'/'.time().'.pdf');
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
    
    protected function getTrustCorp($attorneys)
    {
        foreach($attorneys as $attorney) {
            if($attorney instanceof TrustCorporation) {
                return $attorney;
            }
        }
    
        return null;
    }
    
    protected function deleteTrustCorp($type = null)
    {
        if($type) {
            $i = 0;
            foreach($this->lpa->document->{$type.'Attorneys'} as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    unset($this->lpa->document->{$type.'Attorneys'}[$i]);
                    break;
                }
                $i++;
            }
        }
        else {
            $i = 0;
            foreach($this->lpa->document->primaryAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    unset($this->lpa->document->primaryAttorneys[$i]);
                    break;
                }
                $i++;
            }
            
            $i = 0;
            foreach($this->lpa->document->replacementAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    unset($this->lpa->document->replacementAttorneys[$i]);
                    break;
                }
                $i++;
            }
        }
    }
    
    protected function getHumanAttorneys($type = null)
    {
        if($type) {
            $attorneys = $this->lpa->document->{$type.'Attorneys'};
        }
        else {
            $attorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        }
        
        $i = 0;
        foreach($attorneys as $attorney) {
            if($attorney instanceof TrustCorporation) {
                unset($attorneys[$i]);
                break;
            }
            $i++;
        }
        
        return $attorneys;
    }
    
    protected function getAdditionalPeopleForCS1()
    {
        $attorneys=[];
        $replacements=[];
        $notified=[];
        
        if(count($this->lpa->document->primaryAttorneys) > Lp1::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            foreach($this->lpa->document->primaryAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    array_unshift($attorneys, $attorney);
                }
                else {
                    $attorneys[] = $attorney;
                }
            }
            
            for($i=0; $i<Lp1::MAX_ATTORNEYS_ON_STANDARD_FORM; $i++) array_shift($attorneys);
        }
        
        if(count($this->lpa->document->replacementAttorneys) > Lp1::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) {
            foreach($this->lpa->document->replacementAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    array_unshift($replacements, $attorney);
                }
                else {
                    $replacements[] = $attorney;
                }
            }
            for($i=0; $i<Lp1::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM; $i++) array_shift($replacements);
        }
        
        if(count($this->lpa->document->peopleToNotify) > Lp1::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $notified = $this->lpa->document->peopleToNotify;
            for($i=0; $i<Lp1::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM; $i++) array_shift($notified);
        }
        
        return ['primaryAttorney'=>$attorneys, 'replacementAttorney'=>$replacements, 'peopleToNotify'=>$notified];
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        parent::tearDown();
    }
}