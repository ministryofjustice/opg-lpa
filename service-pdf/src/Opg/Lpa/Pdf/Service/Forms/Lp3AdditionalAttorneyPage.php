<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class Lp3AdditionalAttorneyPage extends AbstractForm
{
    /**
     * If there are more than 4 primary attorneys, duplicate page 3 - About the attorneys, to fit all attorneys in to the form.
     */
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }
    
    public function generate()
    {
        $noOfAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfAttorneys <= Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM) {
            return;
        }
        
        $additionalAttorneys = $noOfAttorneys - Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM;
        $additionalPages = ceil($additionalAttorneys/Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM);
        $mappedAttorneys = 0;
        for($i=0; $i<$additionalPages; $i++) {
            $filePath = $this->registerTempFile('AdditionalAttorneys');
            
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
            
            $additionalAttorneys = count($this->lpa->document->primaryAttorneys) - Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM;
            for($j=0; $j < Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM; $j++) {
                if($mappedAttorneys >= $additionalAttorneys) break;
            
                $attorneyIndex = Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM * ( 1 + $i ) + $j;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-name-title']        = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->title;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-name-first']        = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->first;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-name-last']         = $this->lpa->document->primaryAttorneys[$attorneyIndex]->name->last;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address1']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address1;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address2']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address2;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-address-address3']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->address3;
                $mappings['lpa-document-primaryAttorneys-'.$j.'-address-postcode']  = $this->lpa->document->primaryAttorneys[$attorneyIndex]->address->postcode;
            
                if(++$mappedAttorneys >= $additionalAttorneys) {
                    break;
                }
            }
            
            $additionalAttorneyPage = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LP3_AdditionalAttorney.pdf");
            $additionalAttorneyPage
                ->fillForm($mappings)
                ->needAppearances()
                ->flatten()
                ->saveAs($filePath);
            
            if($additionalAttorneys % Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                $strokeParams = array(array());
                for($i=Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM-$additionalAttorneys%Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM; $i>=1; $i--) {
                    // draw on page 0.
                    $strokeParams[0][] = 'lp3-primaryAttorney-' . (Lp3::MAX_ATTORNEYS_ON_STANDARD_FORM-$i);
                }
                $this->stroke($filePath, $strokeParams);
            }
            
        } //endfor
        
        return $this->intermediateFilePaths;
        
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Lp3AdditionalApplicantPage