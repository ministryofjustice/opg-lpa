<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf as PdftkInstance;

class Lp3 extends AbstractForm
{
    private $basePdfTemplate;
    
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP3');
        
        $this->basePdfTemplate = $this->pdfTemplatePath."/LP3.pdf";
    }
    
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     * 
     * @return Form object | null
     */
    public function generate()
    {
        // will not generate pdf if there's no people to notify
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify == 0) {
            throw new \RuntimeException("LP3 is not available for this LPA.");
        }
        
        // generate standard notification letters for each people to be notified.
        foreach($this->lpa->document->peopleToNotify as $peopleToNotify) {
            $this->generateStandardForm($peopleToNotify);
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
        $pdf = new PdftkInstance($this->basePdfTemplate);
        
        $filePath = $this->registerTempFile('LP3');
        
        // populate forms
        $mappings = $this->dataMapping($peopleToNotify);
        
        $pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($filePath);
        
        $numOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($numOfAttorneys < self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            $crossLineParams = array(2=>array());
            for($i=self::MAX_ATTORNEYS_ON_STANDARD_FORM - $numOfAttorneys; $i>=1; $i--) {
                // draw on page 2.
                $crossLineParams[2][] = 'lp3-primaryAttorney-' . (self::MAX_ATTORNEYS_ON_STANDARD_FORM - $i);
            }
            $this->drawCrossLines($filePath, $crossLineParams);
        }
    } // function generateStandardForm()
    
    /**
     * Data mapping
     * @param NotifiedPerson $peopleToNotify
     * @return array
     */
    protected function dataMapping(NotifiedPerson $peopleToNotify)
    {
        $this->pdfFormData['lpa-document-peopleToNotify-name-title']         = $peopleToNotify->name->title;
        $this->pdfFormData['lpa-document-peopleToNotify-name-first']         = $peopleToNotify->name->first;
        $this->pdfFormData['lpa-document-peopleToNotify-name-last']          = $peopleToNotify->name->last;
        $this->pdfFormData['lpa-document-peopleToNotify-address-address1']   = $peopleToNotify->address->address1;
        $this->pdfFormData['lpa-document-peopleToNotify-address-address2']   = $peopleToNotify->address->address2;
        $this->pdfFormData['lpa-document-peopleToNotify-address-address3']   = $peopleToNotify->address->address3;
        $this->pdfFormData['lpa-document-peopleToNotify-address-postcode']   = $peopleToNotify->address->postcode;
        
        $this->pdfFormData['lpa-document-donor-name-title']         = $this->lpa->document->donor->name->title;
        $this->pdfFormData['lpa-document-donor-name-first']         = $this->lpa->document->donor->name->first;
        $this->pdfFormData['lpa-document-donor-name-last']          = $this->lpa->document->donor->name->last;
        $this->pdfFormData['lpa-document-donor-address-address1']   = $this->lpa->document->donor->address->address1;
        $this->pdfFormData['lpa-document-donor-address-address2']   = $this->lpa->document->donor->address->address2;
        $this->pdfFormData['lpa-document-donor-address-address3']   = $this->lpa->document->donor->address->address3;
        $this->pdfFormData['lpa-document-donor-address-postcode']   = $this->lpa->document->donor->address->postcode;
        
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->pdfFormData['donor-is-applicant'] = self::CHECK_BOX_ON;
        }
        else {
            $this->pdfFormData['attorney-is-applicant'] = self::CHECK_BOX_ON;
        }
        
        if($this->lpa->document->type == Document::LPA_TYPE_PF) {
            $this->pdfFormData['lpa-type-property-and-financial-affairs'] = self::CHECK_BOX_ON;
        }
        elseif($this->lpa->document->type == Document::LPA_TYPE_HW) {
            $this->pdfFormData['lpa-type-health-and-welfare'] = self::CHECK_BOX_ON;
        }
        
        if(count($this->lpa->document->primaryAttorneys) == 1) {
            $this->pdfFormData['only-one-attorney-appointed'] = self::CHECK_BOX_ON;
        }
        
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            if($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
                $this->pdfFormData['attorneys-act-jointly-and-severally'] = self::CHECK_BOX_ON;
            }
            elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
                $this->pdfFormData['attorneys-act-jointly'] = self::CHECK_BOX_ON;
            }
            elseif($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $this->pdfFormData['attorneys-act-upon-decisions'] = self::CHECK_BOX_ON;
            }
        }
        
        $i=0;
        foreach($this->lpa->document->primaryAttorneys as $attorney) {
            if($attorney instanceof TrustCorporation) {
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name;
            }
            else {
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-title'] = $attorney->name->title;
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-first'] = $attorney->name->first;
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $attorney->name->last;
            }
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address1'] = $attorney->address->address1;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address2'] = $attorney->address->address2;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address3'] = $attorney->address->address3;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-postcode'] = $attorney->address->postcode;
            
            if(++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) break;
        }
        
        $this->pdfFormData['footer_right'] = Config::getInstance()['footer']['lp3'];
        
        return $this->pdfFormData;
    } // function dataMapping()

    /**
     * Merge intermediate pdf files into one file.
     */
    protected function mergePdfs()
    {
        if($this->countIntermediateFiles() == 1) {
            $this->generatedPdfFilePath = $this->interFileStack['LP3'][0];
            return;
        }
        
        $pdf = new PdftkInstance();
    
        $intPdfHandle = 'A';
        foreach($this->interFileStack['LP3'] as $lp3Path) {
            $pdf->addFile($lp3Path, $intPdfHandle);
    
            if(isset($this->interFileStack['AdditionalAttorneys'])) {
                $baseHandle = $intPdfHandle++;
                $pdf->cat(1, 3, $baseHandle);
    
                foreach($this->interFileStack['AdditionalAttorneys'] as $additionalPage) {
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