<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Formatter;

class Lpa120 extends AbstractForm
{
    private $basePdfTemplate;
    private $additionalAttorneyPdfTemplate;
    
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    
    protected $strokeParams = array(
        'primaryAttorney-1' => array('bx'=>312, 'by'=>458, 'tx'=>552, 'ty'=>602),
        'primaryAttorney-2' => array('bx'=>43,  'by'=>242, 'tx'=>283, 'ty'=>386),
        'primaryAttorney-3' => array('bx'=>312, 'by'=>242, 'tx'=>552, 'ty'=>386),
    );
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP3-' . microtime(true) . '.pdf';
        
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
        
        // generate standard notification letters for each people to be notified.
        for($i=0; $i<$noOfPeopleToNotify; $i++) {
            $this->generateStandardForm($this->lpa->document->peopleToNotify[$i]);
        }
        
        // depending on how many additional primary attorneys in the LPA, generate additional attorney pages.
        $this->generateAdditionalPages();
        
        // merge intermediate files.
        $this->mergePdfs();
        
        return $this;
        
    } // function generate()
    
    /**
     * Fill LP3 form with values in the data model object.
     * 
     * @param NotifiedPerson $peopleToNotify
     */
    protected function generateStandardForm(NotifiedPerson $peopleToNotify)
    {
        $pdf = PdfProcessor::getPdftkInstance($this->basePdfTemplate);
        
        $filePath = $this->registerTempFile('LP3');
        
        // populate forms
        $mappings = $this->dataMappingForStandardForm($peopleToNotify);
        
        $pdf->fillForm($mappings)
	        ->needAppearances()
            ->flatten()
            ->saveAs($filePath);
        
        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $strokeParams = array(2=>array());
            for($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i>=1; $i--) {
                // draw on page 2.
                $strokeParams[2][] = 'primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
            $this->stroke($filePath, $strokeParams);
        }
    } // function generateStandardForm(NotifiedPerson $peopleToNotify)
    
    /**
     * If there are more than 4 primary attorneys, duplicate page 3 - About the attorneys, to fit all attorneys in to the form.
     */
    protected function generateAdditionalPages()
    {
        $noOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfAttorneys <= self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            return;
        }
        
        $additionalAttorneys = $noOfAttorneys - self::MAX_ATTORNEYS_ON_STANDARD_FORM;
        $additionalPages = ceil($additionalAttorneys/self::MAX_ATTORNEYS_ON_STANDARD_FORM);
        for($i=0; $i<$additionalPages; $i++) {
            $tmpFilePath = $this->registerTempFile('AdditionalAttorneys');
            
            $mappings = $this->dataMappingForAdditionalPage($i);
            
            $additionalAttorneyPage = PdfProcessor::getPdftkInstance($this->additionalAttorneyPdfTemplate);
            $additionalAttorneyPage
                ->fillForm($mappings)
                ->needAppearances()
                ->flatten()
                ->saveAs($tmpFilePath);
            
            if($additionalAttorneys % self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                $strokeParams = array(array());
                for($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM-$additionalAttorneys%self::MAX_ATTORNEYS_ON_STANDARD_FORM; $i>=1; $i--) {
                    // draw on page 0.
                    $strokeParams[0][] = 'primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM-$i);
                }
                
                $this->stroke($tmpFilePath, $strokeParams);
            }
            
        } //endfor
    } // function generateAdditionalPages()
    
    /**
     * Merge intermediate pdf files into one file.
     */
    protected function mergePdfs()
    {
        $pdf = PdfProcessor::getPdftkInstance();
        
        $intPdfHandle = 'A';
        foreach($this->intermediateFilePaths['LP3'] as $lp3Path) {
            $pdf->addFile($lp3Path, $intPdfHandle);
            
            if(isset($this->intermediateFilePaths['AdditionalAttorneys'])) {
                $baseHandle = $intPdfHandle++;
                $pdf->cat(1, 3, $baseHandle);
            
                foreach($this->intermediateFilePaths['AdditionalAttorneys'] as $additionalPage) {
                    $pdf->addFile($additionalPage, $intPdfHandle);
                    $pdf->cat(1, null, $intPdfHandle++);
                }
                
                $pdf->cat(4, null, $baseHandle);
            }
            else {
                $intPdfHandle++;
            }
        } // endfor
        
        $pdf->saveAs($this->generatedPdfFilePath);
    } // function mergePdfs()
    
    /**
     * Data mapping
     * @param NotifiedPerson $peopleToNotify
     * @return array:
     */
    protected function dataMappingForStandardForm(NotifiedPerson $peopleToNotify)
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
        
        return $this->flattenLpa;
    } // function modelPdfFieldDataMapping()
    
    protected function dataMappingForAdditionalPage($additionalPageIndex)
    {
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
        
        $additionalAttorneys = count($this->lpa->document->primaryAttorneys) - self::MAX_ATTORNEYS_ON_STANDARD_FORM;
        $mappedAttorneys = 0;
        for($j=0; $j < self::MAX_ATTORNEYS_ON_STANDARD_FORM; $j++) {
            if($mappedAttorneys >= $additionalAttorneys) break;
        
            $attorneyIndex = self::MAX_ATTORNEYS_ON_STANDARD_FORM * ( 1 + $additionalPageIndex ) + $j;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-name-title']        = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->title;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-name-first']        = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->first;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-name-last']         = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->last;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address1']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address1;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address2']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address2;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address3']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address3;
            $mappings['lpa-document-primaryAttorneys-'.$j.'-address-postcode']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->postcode;
        
            $mappedAttorneys++;
        }
        
        return $mappings;
    }
}