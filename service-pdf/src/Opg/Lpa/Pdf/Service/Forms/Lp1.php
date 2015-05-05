<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

abstract class Lp1 extends AbstractForm
{
    const BOX_CHARS_PER_ROW = 84;
    const BOX_NO_OF_ROWS = 6;
    
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    const MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM = 2;
    const MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM = 4;
    
    const MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM = 4;
    
    const MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM = 4;
    
    const MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM = 4;
    
    /**
     *
     * @var PDFTK pdf object
     */
    protected $pdf;
    
    /**
     * Populate LPA data into PDF forms, generate pdf file.
     *
     * @return Form object
     */
    public function generate()
    {
        $this->generateStandardForm();
        $this->generateAdditionalPages();
        $this->mergePdfs();
        
        return $this;
        
    } // function generate()
    
    /**
     * Populate LP1F/H base template PDF and generate as tmeporary pdf for merging additional pages if needed to.
     */
    protected function generateStandardForm()
    {
        $filePath = $this->registerTempFile('LP1');
        
        // data mapping
        $mappings = $this->dataMapping();
        
        // populate form data and generate pdf
        $this->pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($filePath);
        
        // draw cross lines if there's any blank slot
        if(!empty($this->drawingTargets)) {
            $this->drawCrossLines($filePath, $this->drawingTargets);
        }
        
    } // function generateDefaultPdf()
    
    /**
     * Generate additional pages depending on the LPA's composition.
     */
    protected function generateAdditionalPages()
    {
        // CS1 is generated when number of people to notify are larger than what is available on standard form. 
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > 4) {
            $generatedCs1 = (new Cs1($this->lpa, 'peopleToNotify', $this->lpa->document->peopleToNotify))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        // generate a CS2 page if attorneys act depend on a special decision.
        if($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_ATTORNEY_DECISIONS, $this->lpa->document->primaryAttorneyDecisions->howDetails))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        // generate a CS2 page if replacement attorneys decision differ to standard arrangement.
        if((count($this->lpa->document->replacementAttorneys) > 1) && 
            ($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
            
            $content = "";
            switch($this->lpa->document->replacementAttorneyDecisions->how) {
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $content = "Replacement attorneys make decisions jointly and severally\r\n";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $content = "Replacement attorneys make decisions depend on below\r\n" . $this->lpa->document->replacementAttorneyDecisions->howDetails . "\r\n";
            }
            
            switch($this->lpa->document->replacementAttorneyDecisions->when) {
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST:
                    $content .= "Replacement attorneys step in when the first attorney is unable to act\r\n";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST:
                    $content .= "Replacement attorneys step in when the last attorney is unable to act\r\n";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS:
                    $content .= "Replacement attorneys step in depends on below\r\n" . $this->lpa->document->replacementAttorneyDecisions->whenDetails;
                    break;
            }
            
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN, $content))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        } // endif
        
        // generate a CS2 page if preference exceed available space on standard form.
        if(!$this->canFitIntoTextBox($this->lpa->document->preference)) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_PREFERENCES, $this->lpa->document->preference))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        // generate a CS2 page if instruction exceed available space on standard form.
        if(!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_INSTRUCTIONS, $this->lpa->document->instruction))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        // generate CS3 page if donor cannot sign on LPA
        if(false === $this->lpa->document->donor->canSign) {
            $generatedCs3 = (new Cs3($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedCs3);
        }
        
        // if number of applicant is greater than 4, duplicate Section 12 - Applicants 
        // as many as needed to be able to fit all applicants in.
        if(is_array($this->lpa->document->whoIsRegistering) && (count($this->lpa->document->whoIsRegistering)>4)) {
            $generatedAdditionalApplicantPages = (new Lp1AdditionalApplicantPage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalApplicantPages);
        }
        
    } // function generateAdditionalPagePdfs()
    
    protected function dataMapping()
    {
        $this->flattenLpa['lpa-id'] = Formatter::id($this->lpa->id);
        
        $this->flattenLpa['lpa-document-donor-dob-date-day'] =  $this->lpa->document->donor->dob->date->format('d');
        $this->flattenLpa['lpa-document-donor-dob-date-month'] = $this->lpa->document->donor->dob->date->format('m');
        $this->flattenLpa['lpa-document-donor-dob-date-year'] = $this->lpa->document->donor->dob->date->format('Y');
        
        /**
         * attorneys section (section 2)
         */
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->drawingTargets[1] = array('primaryAttorney-1');
            $this->drawingTargets[2] = array('primaryAttorney-2', 'primaryAttorney-3');
        }
        elseif($noOfPrimaryAttorneys > 4) {
            $this->flattenLpa['has-more-than-4-attorneys'] = self::CHECK_BOX_ON;
        }
        else {
            if($noOfPrimaryAttorneys == 2) {
                $this->drawingTargets[2] = array('primaryAttorney-2', 'primaryAttorney-3');
            }
            elseif($noOfPrimaryAttorneys == 3) {
                $this->drawingTargets[2] = array('primaryAttorney-3');
            }
        }
        
        /**
         * attorney decision section (section 3)
         */
        if($noOfPrimaryAttorneys > 1) {
            switch($this->flattenLpa['lpa-document-primaryAttorneyDecisions-how']) {
                case Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    $this->flattenLpa['attorneys-act-jointly'] = self::CHECK_BOX_ON;
                    break;
                case Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $this->flattenLpa['attorneys-act-jointly-and-severally'] = self::CHECK_BOX_ON;
                    break;
                case Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $this->flattenLpa['attorneys-act-upon-decisions'] = self::CHECK_BOX_ON;
                    break;
                default:
                    break;
            }
        }
        else {
            $this->flattenLpa['only-one-attorney-appointed'] = self::CHECK_BOX_ON;
        }
        
        /**
         * replacement attorneys section (section 4)
         */
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $this->flattenLpa['has-more-than-2-replacement-attorneys'] = self::CHECK_BOX_ON;
        }
        elseif($noOfReplacementAttorneys == 1) {
            $this->drawingTargets[4] = array('replacementAttorney-1');
        }
        elseif($noOfReplacementAttorneys == 0) {
            $this->drawingTargets[4] = array('replacementAttorney-0', 'replacementAttorney-1');
        }
        
        if(($noOfReplacementAttorneys > 1) && ($this->lpa->document->replacementAttorneyDecisions instanceof Decisions\ReplacementAttorneyDecisions) &&
            ($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                $this->flattenLpa['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
        }
        
        /**
         * People to notify (Section 6)
         */
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->flattenLpa['has-more-than-4-notified-people'] = self::CHECK_BOX_ON;
        }
        elseif($noOfPeopleToNotify < self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->drawingTargets[6] = array();
            for($i=self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $noOfPeopleToNotify; $i>0; $i--) {
                $this->drawingTargets[6][] = 'people-to-notify-'. (self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $i);
            }
        }
        
        /**
         *  Preference and Instructions. (Section 7)
         */
        if(!empty($this->flattenLpa['lpa-document-preference'])) {
            if(!$this->canFitIntoTextBox($this->flattenLpa['lpa-document-preference'])) {
                $this->flattenLpa['has-more-preferences'] = self::CHECK_BOX_ON;
            }
            $this->flattenLpa['lpa-document-preference'] = $this->getContentForBox(0, $this->flattenLpa['lpa-document-preference'], self::CONTENT_TYPE_PREFERENCES);
        }
        
        if(!empty($this->flattenLpa['lpa-document-instruction'])) {
            if(!$this->canFitIntoTextBox($this->flattenLpa['lpa-document-instruction'])) {
                $this->flattenLpa['has-more-instructions'] = self::CHECK_BOX_ON;
            }
            $this->flattenLpa['lpa-document-instruction'] = $this->getContentForBox(0, $this->flattenLpa['lpa-document-instruction'], self::CONTENT_TYPE_INSTRUCTIONS);
        }
        
        /**
         * Populate primary and replacement attorneys signature pages (Section 11)
         */
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $attorneyIndex=0;
        foreach($allAttorneys as $attorney) {
            
            if($attorney instanceof TrustCorporation) continue;
            
            $this->flattenLpa['signature-attorney-'.$attorneyIndex.'-name-title'] = $attorney->name->title;
            $this->flattenLpa['signature-attorney-'.$attorneyIndex.'-name-first'] = $attorney->name->first;
            $this->flattenLpa['signature-attorney-'.$attorneyIndex.'-name-last'] = $attorney->name->last;
            $attorneyIndex++;
        }
        
        $numberOfHumanAttorneys = $attorneyIndex;
        
        switch($numberOfHumanAttorneys) {
            case 3:
                $this->drawingTargets[14] = array('attorney-signature');
                break;
            case 2:
                $this->drawingTargets[13] = array('attorney-signature');
                $this->drawingTargets[14] = array('attorney-signature');
                break;
            case 1:
                $this->drawingTargets[12] = array('attorney-signature');
                $this->drawingTargets[13] = array('attorney-signature');
                $this->drawingTargets[14] = array('attorney-signature');
                break;
        }
        
        /**
         * Applicant (Section 12)
         */
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->flattenLpa['donor-is-applicant'] = self::CHECK_BOX_ON;
            $this->drawingTargets[16] = array('applicant-0','applicant-1','applicant-2','applicant-3');
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            $this->flattenLpa['attorney-is-applicant'] = self::CHECK_BOX_ON;
            foreach($this->lpa->document->whoIsRegistering as $index=>$attorneyId) {
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);
                if($attorney instanceof TrustCorporation) {
                    $this->flattenLpa['applicant-'.$index.'-name-last']      = $attorney->name;
                }
                else {
                    $this->flattenLpa['applicant-'.$index.'-name-title']     = $attorney->name->title;
                    $this->flattenLpa['applicant-'.$index.'-name-first']     = $attorney->name->first;
                    $this->flattenLpa['applicant-'.$index.'-name-last']      = $attorney->name->last;
                    $this->flattenLpa['applicant-'.$index.'-dob-date-day']   = $attorney->dob->date->format('d');
                    $this->flattenLpa['applicant-'.$index.'-dob-date-month'] = $attorney->dob->date->format('m');
                    $this->flattenLpa['applicant-'.$index.'-dob-date-year']  = $attorney->dob->date->format('Y');
                }
            }
            
            switch(count($this->lpa->document->whoIsRegistering)) {
                case 3:
                    $this->drawingTargets[16] = array('applicant-3');
                    break;
                case 2:
                    $this->drawingTargets[16] = array('applicant-2','applicant-3');
                    break;
                case 1:
                    $this->drawingTargets[16] = array('applicant-1','applicant-2','applicant-3');
                    break;
            }
        }
        
        /**
         * Correspondent (Section 13)
         */
        if($this->lpa->document->correspondent instanceof Correspondence) {
            switch($this->flattenLpa['lpa-document-correspondent-who']) {
                case Correspondence::WHO_DONOR:
                    $this->flattenLpa['donor-is-correspondent'] = self::CHECK_BOX_ON;
                    $this->flattenLpa['lpa-document-correspondent-name-title'] = null;
                    $this->flattenLpa['lpa-document-correspondent-name-first'] = null;
                    $this->flattenLpa['lpa-document-correspondent-name-last'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-address1'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-address2'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-address3'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-postcode'] = null;
                    break;
                case Correspondence::WHO_ATTORNEY:
                    $this->flattenLpa['attorney-is-correspondent'] = self::CHECK_BOX_ON;
                    $this->flattenLpa['lpa-document-correspondent-address-address1'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-address2'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-address3'] = null;
                    $this->flattenLpa['lpa-document-correspondent-address-postcode'] = null;
                    break;
                case Correspondence::WHO_OTHER:
                    $this->flattenLpa['other-is-correspondent'] = self::CHECK_BOX_ON;
                    break;
            }
            
            if($this->flattenLpa['lpa-document-correspondent-contactByPost'] === true) {
                $this->flattenLpa['correspondent-contact-by-post'] = self::CHECK_BOX_ON;
            }
            
            if(isset($this->flattenLpa['lpa-document-correspondent-phone-number'])) {
                $this->flattenLpa['correspondent-contact-by-phone'] = self::CHECK_BOX_ON;
            }
            
            if(isset($this->flattenLpa['lpa-document-correspondent-email-address'])) {
                $this->flattenLpa['correspondent-contact-by-email'] = self::CHECK_BOX_ON;
            }
            
            if($this->flattenLpa['lpa-document-correspondent-contactInWelsh'] === true) {
                $this->flattenLpa['correspondent-contact-in-welsh'] = self::CHECK_BOX_ON;
            }
        }
        
        /**
         *  Payment section (section 14)
         */
        // Fee reduction, repeat application
        if($this->lpa->repeatCaseNumber !== null) {
            $this->flattenLpa['is-repeat-application'] = self::CHECK_BOX_ON;
            $this->flattenLpa['repeat-application-case-number'] = $this->lpa->repeatCaseNumber;
        }
        
        if($this->lpa->payment instanceof Payment) {
            // payment method
            if($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CARD) {
                $this->flattenLpa['pay-by-card'] = self::CHECK_BOX_ON;
                $this->flattenLpa['lpa-payment-phone-number'] = "NOT REQUIRED. PAYMENT MADE ONLINE.";
                
            }
            elseif($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CHEQUE) {
                $this->flattenLpa['pay-by-cheque'] = self::CHECK_BOX_ON;
            }
        
            if($this->lpa->payment->reducedFeeLowIncome || 
                ($this->lpa->payment->reducedFeeReceivesBenefits && $this->lpa->payment->reducedFeeAwardedDamages) ||
                $this->lpa->payment->reducedFeeUniversalCredit) {
                
                $this->flattenLpa['apply-for-fee-reduction'] = self::CHECK_BOX_ON;
            }
            
            // Online payment details
            if(isset($this->flattenLpa['lpa-payment-reference'])) {
                $this->flattenLpa['lpa-payment-amount'] = '£'.sprintf('%.2f', $this->flattenLpa['lpa-payment-amount']);
                $this->flattenLpa['lpa-payment-date-day'] = $this->lpa->payment->date->format('d');
                $this->flattenLpa['lpa-payment-date-month'] = $this->lpa->payment->date->format('m');
                $this->flattenLpa['lpa-payment-date-year'] = $this->lpa->payment->date->format('Y');
            }
        }
        
        return $this->flattenLpa;
        
    } // function dataMapping()
    
    /**
     * Merge generated intermediate pdf files
     */
    protected function mergePdfs()
    {
        if($this->countIntermediateFiles() == 1) {
            $this->generatedPdfFilePath = $this->interFileStack['LP1'][0];
            return;
        }
        
        $pdf = PdfProcessor::getPdftkInstance();
        $intPdfHandle = 'A';
        if(isset($this->interFileStack['LP1'])) {
            $pdf->addFile($this->interFileStack['LP1'], $intPdfHandle);
        }
        else {
            throw new \UnexpectedValueException('LP1 pdf was not generated before merging pdf intermediate files');
        }
        
        // add page 1-15
        $pdf->cat(1, 15, 'A');
        
        // Section 11 - additional attorneys signature
        if(isset($this->interFileStack['AdditionalAttorneySignature'])) {
            foreach($this->interFileStack['AdditionalAttorneySignature'] as $additionalAttorneySignature) {
                $pdf->addFile($additionalAttorneySignature, ++$intPdfHandle);
                
                // add an additional attorney signature page
                $pdf->cat(1, null, $intPdfHandle);
            }
        }
        
        // add page 16, 17
        $pdf->cat(16, 17, 'A');
        
        // Section 12 additional applicants
        if(isset($this->interFileStack['AdditionalApplicant'])) {
            foreach($this->interFileStack['AdditionalApplicant'] as $additionalApplicant) {
                $pdf->addFile($additionalApplicant, ++$intPdfHandle);
                
                // add an additional applicant page
                $pdf->cat(1, null, $intPdfHandle);
            }
        }
        
        // add page 18, 19, 20
        $pdf->cat(18, 20, 'A');
        
        // Section 15 - additional applicants signature
        if(($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) && ($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) &&
                (count($this->lpa->document->primaryAttorneys) > self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM)) {
            $totalAdditionalApplicants = count($this->lpa->document->primaryAttorneys) - self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
            $totalAdditionalPages = ceil($totalAdditionalApplicants/self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);
        }
        elseif(is_array($this->lpa->document->whoIsRegistering) &&
                (count($this->lpa->document->whoIsRegistering) > self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM)) {
            $totalAdditionalApplicants = count($this->lpa->document->whoIsRegistering) - self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
            $totalAdditionalPages = ceil($totalAdditionalApplicants/self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);
        }
        
        if(isset($totalAdditionalPages) && ($totalAdditionalPages > 0)) {
            for($i=0; $i<$totalAdditionalPages; $i++) {
                $pdf->addFile($this->pdfTemplatePath."/LP1_AdditionalApplicantSignature.pdf", ++$intPdfHandle);
                
                // add an additional applicant signature page
                $pdf->cat(1, null, $intPdfHandle);
            }
        }
        
        // Continuation Sheet 1
        if(isset($this->interFileStack['CS1'])) {
            foreach ($this->interFileStack['CS1'] as $cs1) {
                $pdf->addFile($cs1, ++$intPdfHandle);
                
                // add a CS1 page
                $pdf->cat(1, null, $intPdfHandle);
            }
        }
        
        // Continuation Sheet 2
        if(isset($this->interFileStack['CS2'])) {
            foreach ($this->interFileStack['CS2'] as $cs2) {
                $pdf->addFile($cs2, ++$intPdfHandle);
                
                // add a CS2 page
                $pdf->cat(1, null, $intPdfHandle);
            }
        }
        
        // Continuation Sheet 3
        if(isset($this->interFileStack['CS3'])) {
            $pdf->addFile($this->interFileStack['CS3'], ++$intPdfHandle);
            
            // add a CS3 page
            $pdf->cat(1, null, $intPdfHandle);
        }
        
        // Continuation Sheet 4
        if(isset($this->interFileStack['CS4'])) {
            $pdf->addFile($this->interFileStack['CS4'], ++$intPdfHandle);
            
            // add a CS4 page
            $pdf->cat(1, null, $intPdfHandle);
        }
        
        $pdf->saveAs($this->generatedPdfFilePath);
        
    } // function mergePdfs()
} // class Lp1