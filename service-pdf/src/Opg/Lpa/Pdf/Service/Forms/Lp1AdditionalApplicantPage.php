<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Lp1AdditionalApplicantPage extends AbstractForm
{
    /**
     * Duplicate Section 12 page for additional applicants
     * 
     * @param Lpa $lpa
     */
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }
    
    public function generate()
    {
        $totalApplicant = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicant = $totalApplicant - self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM;
        $totalAdditionalPages = ceil($totalAdditionalApplicant/self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM);
        
        $totalMappedAdditionalApplicants = 0;
        for($i=0; $i<$totalAdditionalPages; $i++) {
            
            $filePath = $this->registerTempFile('AdditionalApplicant');
            
            $additionalApplicant = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LP1_AdditionalApplicant.pdf");
            $formData = array();
            for($j=0; $j<self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $j++) {
                $attorneyId = $this->lpa->document->whoIsRegistering[(1+$i)*self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM + $j];
                
                $formData['applicant-'.$j.'-name-title']     = $this->lpa->document->primaryAttorneys[$attorneyId]->name->title;
                $formData['applicant-'.$j.'-name-first']     = $this->lpa->document->primaryAttorneys[$attorneyId]->name->first;
                $formData['applicant-'.$j.'-name-last']      = $this->lpa->document->primaryAttorneys[$attorneyId]->name->last;
                $formData['applicant-'.$j.'-dob-date-day']   = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('d');
                $formData['applicant-'.$j.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('m');
                $formData['applicant-'.$j.'-dob-date-year']  = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('Y');
                
                if(++$totalMappedAdditionalApplicants >= $totalAdditionalApplicant) {
                    break;
                }
            } // endfor
            
            $formData['attorney-is-applicant'] = self::CHECK_BOX_ON;
            
            $additionalApplicant->fillForm($formData)
                ->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($additionalApplicant);
        
        } // endfor
        
        // draw strokes if there's any blank slot
        if($totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
            $strokeParams = array(array());
            for($i=self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $i>=1; $i--) {
                $strokeParams[0][] = 'additional-applicant-'.(self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $i);
            }
            $this->stroke($filePath, $strokeParams);
        }
        
        return $this->intermediateFilePaths;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class AdditionalApplicantPage