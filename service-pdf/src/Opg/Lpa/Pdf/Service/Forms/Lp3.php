<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Config\Config;
use ZendPdf\PdfDocument;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Formatter;

class Lp3 extends AbstractForm
{
    private $basePdfTemplate;
    private $additionalAttorneyPdfTemplate;
    
    protected $strokeParams = array(
        'primaryAttorney-1'     => array('bx'=>312,'by'=>458,'tx'=>552,'ty'=>602),
        'primaryAttorney-2'     => array('bx'=>43,'by'=>242,'tx'=>283,'ty'=>386),
        'primaryAttorney-3'     => array('bx'=>312,'by'=>242,'tx'=>552,'ty'=>386),
    );
    
    public function __construct(Lpa $lpa, Config $config)
    {
        parent::__construct($lpa, $config);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP3-' . time() . '.pdf';
        
        $this->basePdfTemplate = $this->basePdfTemplatePath."/LP3.pdf";
        $this->additionalAttorneyPdfTemplate = $this->basePdfTemplatePath."/LP3_AdditionalAttorney.pdf";
    }
    
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return Form object
     */
    public function generate()
    {
        // will not generate pdf if there's no people to notify
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify == 0) return;
        
        $this->intermediatePdfFilePaths['LP3'] = array();
        
        for($i=0; $i<$noOfPeopleToNotify; $i++) {
            $this->generateDefaultPdf($this->lpa->document->peopleToNotify[$i]);
        }
        
        $this->generateAdditionalPagePdfs();
        
        $this->mergePdfs();
        return $this;
        
    } // function generate()
    
    protected function generateDefaultPdf($peopleToNotify)
    {
        $pdf = new Pdf($this->basePdfTemplate);
        
        $tmpSavePath = '/tmp/pdf-LP3-'.$this->lpa->id.'-'.microtime(true).'.pdf';
        $this->intermediatePdfFilePaths['LP3'][] = $tmpSavePath;
        
        // populate forms
        $this->modelPdfFieldDataMapping($peopleToNotify);
        $pdf->fillForm($this->flattenLpa)
	        ->needAppearances()
            ->flatten()
            ->saveAs($tmpSavePath);
        
        $noOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfAttorneys < 4) {
            // draw strokes
            $pdf = PdfDocument::load($tmpSavePath);
            
            $page = $pdf->pages[2]->setLineWidth(self::STROKE_LINE_WIDTH);
            $page->drawLine(
                $this->strokeParams['primaryAttorney-3']['bx'],
                $this->strokeParams['primaryAttorney-3']['by'],
                $this->strokeParams['primaryAttorney-3']['tx'],
                $this->strokeParams['primaryAttorney-3']['ty']
            );
            
            if($noOfAttorneys < 3) {
                $page->drawLine(
                    $this->strokeParams['primaryAttorney-2']['bx'],
                    $this->strokeParams['primaryAttorney-2']['by'],
                    $this->strokeParams['primaryAttorney-2']['tx'],
                    $this->strokeParams['primaryAttorney-2']['ty']
                );
            }
            
            if($noOfAttorneys < 2) {
                $page->drawLine(
                $this->strokeParams['primaryAttorney-1']['bx'],
                $this->strokeParams['primaryAttorney-1']['by'],
                $this->strokeParams['primaryAttorney-1']['tx'],
                $this->strokeParams['primaryAttorney-1']['ty']
                );
            }
            
            $pdf->save($tmpSavePath);
        }
    }
    
    protected function generateAdditionalPagePdfs()
    {
        $noOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfAttorneys > 4) {
            $additionalAttorneys = $noOfAttorneys-4;
            $additionalPages = ceil($additionalAttorneys/4);
            $this->intermediatePdfFilePaths['AdditionalAttorneys'] = array();
            $mappedAttorneys = 0;
            for($i=0; $i<$additionalPages; $i++) {
                $tmpSavePath = '/tmp/pdf-LP3-'.$this->lpa->id.'-'.microtime(true).'.pdf';
                $this->intermediatePdfFilePaths['AdditionalAttorneys'][] = $tmpSavePath;
                
                $mappings = array();
                
                if($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
                    $mappings['attorneys-act-jointly-and-severally'] = self::CHECK_BOX_ON;
                }
                elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
                    $mappings['attorneys-act-jointly'] = self::CHECK_BOX_ON;
                }
                elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $mappings['attorneys-act-upon-decisions'] = self::CHECK_BOX_ON;
                }
                
                for($j=0; $j<4; $j++) {
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-name-title'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->name->title;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-name-first'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->name->first;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-name-last'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->name->last;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address1'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->address->address1;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address2'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->address->address2;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address3'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->address->address3;
                    $mappings['lpa-document-primaryAttorneys-'.$j.'-address-postcode'] = $this->lpa->document->primaryAttorneys[4+$i*4+$j]->address->postcode;
                    
                    if(++$mappedAttorneys >= $additionalAttorneys) break;
                }
                
                $additionalAttorneyPage = new Pdf($this->additionalAttorneyPdfTemplate);
                $additionalAttorneyPage
                    ->fillForm($mappings)
                    ->needAppearances()
                    ->flatten()
                    ->saveAs($tmpSavePath);
                
                if($j <= 3) {
                    // draw strokes
                    $pdf = PdfDocument::load($tmpSavePath);
                
                    $page = $pdf->pages[0]->setLineWidth(self::STROKE_LINE_WIDTH);
                    $page->drawLine(
                    $this->strokeParams['primaryAttorney-3']['bx'],
                    $this->strokeParams['primaryAttorney-3']['by'],
                    $this->strokeParams['primaryAttorney-3']['tx'],
                    $this->strokeParams['primaryAttorney-3']['ty']
                    );
                
                    if($j <= 2) {
                        $page->drawLine(
                        $this->strokeParams['primaryAttorney-2']['bx'],
                        $this->strokeParams['primaryAttorney-2']['by'],
                        $this->strokeParams['primaryAttorney-2']['tx'],
                        $this->strokeParams['primaryAttorney-2']['ty']
                        );
                    }
                
                    if($j <= 1) {
                        $page->drawLine(
                        $this->strokeParams['primaryAttorney-1']['bx'],
                        $this->strokeParams['primaryAttorney-1']['by'],
                        $this->strokeParams['primaryAttorney-1']['tx'],
                        $this->strokeParams['primaryAttorney-1']['ty']
                        );
                    }
                
                    $pdf->save($tmpSavePath);
                }
            }
        }
    }
    
    protected function mergePdfs()
    {
        $pdf = new Pdf();
        
        $intPdfHandle = 'A';
        foreach($this->intermediatePdfFilePaths['LP3'] as $lp3Path) {
            $pdf->addFile($lp3Path, $intPdfHandle);
            
            if(isset($this->intermediatePdfFilePaths['AdditionalAttorneys'])) {
                $baseHandle = $intPdfHandle++;
                $pdf->cat(1, 3, $baseHandle);
            
                foreach($this->intermediatePdfFilePaths['AdditionalAttorneys'] as $additionalPage) {
                    $pdf->addFile($additionalPage, $intPdfHandle);
                    $pdf->cat(1, null, $intPdfHandle++);
                }
                
                $pdf->cat(4, null, $baseHandle);
            }
            else {
                $intPdfHandle++;
            }
        }
        
        $pdf->saveAs($this->generatedPdfFilePath);
    }
    
    private function modelPdfFieldDataMapping(NotifiedPerson $peopleToNotify)
    {
        $this->flattenLpa['lpa-document-peopleToNotify-name-title']         = $peopleToNotify->name->title;
        $this->flattenLpa['lpa-document-peopleToNotify-name-first']         = $peopleToNotify->name->first;
        $this->flattenLpa['lpa-document-peopleToNotify-name-last']          = $peopleToNotify->name->last;
        $this->flattenLpa['lpa-document-peopleToNotify-address-address1']   = $peopleToNotify->address->address1;
        $this->flattenLpa['lpa-document-peopleToNotify-address-address2']   = $peopleToNotify->address->address2;
        $this->flattenLpa['lpa-document-peopleToNotify-address-address3']   = $peopleToNotify->address->address3;
        $this->flattenLpa['lpa-document-peopleToNotify-address-postcode']   = $peopleToNotify->address->postcode;
        
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->flattenLpa['donor-is-applicant'] = self::CHECK_BOX_ON;
        }
        else {
            $this->flattenLpa['attorney-is-applicant'] = self::CHECK_BOX_ON;
        }
        
        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $this->flattenLpa['lpa-type-property-and-financial-affairs'] = self::CHECK_BOX_ON;
        }
        elseif($this->lpa->document->type == Document::LPA_TYPE_HW) {
            $this->flattenLpa['lpa-type-health-and-welfare'] = self::CHECK_BOX_ON;
        }
        
        if(count($this->lpa->document->primaryAttorneys) == 1) {
            $this->flattenLpa['only-one-attorney-appointed'] = self::CHECK_BOX_ON;
        }
        elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
            $this->flattenLpa['attorneys-act-jointly-and-severally'] = self::CHECK_BOX_ON;
        }
        elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
            $this->flattenLpa['attorneys-act-jointly'] = self::CHECK_BOX_ON;
        }
        elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $this->flattenLpa['attorneys-act-upon-decisions'] = self::CHECK_BOX_ON;
        }
    } // function modelPdfFieldDataMapping()
}