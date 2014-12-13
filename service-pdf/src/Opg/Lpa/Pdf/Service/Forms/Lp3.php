<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Formatter;

class Lp3 extends AbstractForm
{
    private $basePdfTemplate;
    
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = '/tmp/pdf-' . Formatter::id($this->lpa->id) .
                 '-LP3-' . microtime(true) . '.pdf';
        
        $this->basePdfTemplate = $this->basePdfTemplatePath."/LP3.pdf";
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
        $generatedAdditionalAttorneyPages = (new Lp3AdditionalAttorneyPage($this->lpa))->generate();
        $this->mergerIntermediateFilePaths($generatedAdditionalAttorneyPages);
        
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
        $mappings = $this->dataMapping($peopleToNotify);
        
        $pdf->fillForm($mappings)
	        ->needAppearances()
            ->flatten()
            ->saveAs($filePath);
        
        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $strokeParams = array(2=>array());
            for($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i>=1; $i--) {
                // draw on page 2.
                $strokeParams[2][] = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
            $this->stroke($filePath, $strokeParams);
        }
    } // function generateStandardForm()
    
    /**
     * Data mapping
     * @param NotifiedPerson $peopleToNotify
     * @return array
     */
    protected function dataMapping(NotifiedPerson $peopleToNotify)
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
    } // function dataMapping()

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
} // class Lp3