<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

abstract class Lp1 extends AbstractForm
{
    const BOX_CHARS_PER_ROW = 84;
    const BOX_NO_OF_ROWS = 11;
    
    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    const MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM = 2;
    const MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM = 4;
    
    const MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM = 4;
    
    const MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM = 4;
    
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
	        ->needAppearances()
            ->flatten()
            ->saveAs($filePath);
//         print_r($this->pdf);
        
        // draw strokes if there's any blank slot
        if(!empty($this->drawingTargets)) {
            $this->stroke($filePath, $this->drawingTargets);
        }
        
    } // function generateDefaultPdf()
    
    /**
     * Generate additional pages depending on the LPA's composition.
     */
    protected function generateAdditionalPages()
    {
        // CS1 is generated when number of attorneys or people to notify are larger than what is available on standard form. 
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys > 4) {
            $generatedCs1 = (new Cs1($this->lpa, 'primaryAttorneys'))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $generatedCs1 = (new Cs1($this->lpa, 'replacementAttorneys'))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > 4) {
            $generatedCs1 = (new Cs1($this->lpa, 'peopleToNotify'))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        // generate a CS2 page if attorneys act depend on a special decision.
        if($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_ATTORNEY_DECISIONS, $this->lpa->document->primaryAttorneyDecisions->howDetails))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        // generate a CS2 page if replacement attorneys decision differ to standard arrangement.
        if(($noOfReplacementAttorneys > 1) && 
            ($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
            
            $how = "";
            $when = "";
            switch($this->lpa->document->replacementAttorneyDecisions->how) {
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $how = "Replacement attorneys make decisions jointly and severally";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $how = "Replacement attorneys make decisions depend on below";
            }
            
            switch($this->lpa->document->replacementAttorneyDecisions->when) {
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST:
                    $when = "Replacement attorneys step in when the first attorney is unable to act";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST:
                    $when = "Replacement attorneys step in when the last attorney is unable to act";
                    break;
                case Decisions\ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS:
                    $when = "Replacement attorneys step in depends on below";
                    break;
            }
            
            $content = (!empty($how)? $how."\n":"") .
                       (!empty($when)? $when."\n":"") .
                       $this->lpa->document->replacementAttorneyDecisions->howDetails . "\n" . 
                       $this->lpa->document->replacementAttorneyDecisions->whenDetails;
            
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
        
        if(($noOfReplacementAttorneys > 1) &&
            ($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                $this->flattenLpa['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
        }
        
        /**
         * People to notify (Section 6)
         */
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->flattenLpa['has-more-than-5-notified-people'] = self::CHECK_BOX_ON;
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
            $this->flattenLpa['lpa-document-preference'] = $this->getContentForBox(0, $this->flattenLpa['lpa-document-preference'], self::CONTENT_TYPE_PREFERENCES);
        }
        
        if(!empty($this->flattenLpa['lpa-document-instruction'])) {
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
                if($this->lpa->document->primaryAttorneys[$attorneyId] instanceof TrustCorporation) {
                    $this->flattenLpa['applicant-'.$index.'-name-last']      = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name'];
                }
                else {
                    $this->flattenLpa['applicant-'.$index.'-name-title']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-title'];
                    $this->flattenLpa['applicant-'.$index.'-name-first']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-first'];
                    $this->flattenLpa['applicant-'.$index.'-name-last']      = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-last'];
                    $this->flattenLpa['applicant-'.$index.'-dob-date-day']   = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('d');
                    $this->flattenLpa['applicant-'.$index.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('m');
                    $this->flattenLpa['applicant-'.$index.'-dob-date-year']  = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('Y');
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
        switch($this->flattenLpa['lpa-document-correspondent-who']) {
            case Correspondence::WHO_DONOR:
                $this->flattenLpa['donor-is-correspondent'] = self::CHECK_BOX_ON;
                break;
            case Correspondence::WHO_ATTORNEY:
                $this->flattenLpa['attorney-is-correspondent'] = self::CHECK_BOX_ON;
                break;
            case Correspondence::WHO_OTHER:
                $this->flattenLpa['other-is-correspondent'] = self::CHECK_BOX_ON;
                break;
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-contactByPost'])) {
            $this->flattenLpa['correspondent-contact-by-post'] = self::CHECK_BOX_ON;
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-phone-number'])) {
            $this->flattenLpa['correspondent-contact-by-phone'] = self::CHECK_BOX_ON;
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-email-address'])) {
            $this->flattenLpa['correspondent-contact-by-email'] = self::CHECK_BOX_ON;
        }
        
        if(isset($this->flattenLpa['lpa-document-correspondent-contactInWelsh'])) {
            $this->flattenLpa['correspondent-contact-in-welsh'] = self::CHECK_BOX_ON;
        }
        
        
        /**
         *  Payment section (section 14)
         */
        // payment method
        if($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CARD) {
            $this->flattenLpa['pay-by-card'] = self::CHECK_BOX_ON;
            $this->flattenLpa['lpa-payment-phone-number'] = "NOT REQUIRED. PAYMENT MADE ONLINE.";
            
        }
        elseif($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CHEQUE) {
            $this->flattenLpa['pay-by-cheque'] = self::CHECK_BOX_ON;
        }
        
        
        // Fee reduction, repeat application
        if($this->lpa->repeatCaseNumber !== null) {
            $this->flattenLpa['is-repeat-application'] = self::CHECK_BOX_ON;
            $this->flattenLpa['repeat-application-case-number'] = $this->lpa->repeatCaseNumber;
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
        
        return $this->flattenLpa;
        
    } // function dataMapping()
    
    /**
     * Merge generated intermediate pdf files
     */
    protected function mergePdfs()
    {
        if($this->countIntermediateFiles() == 1) {
            $this->generatedPdfFilePath = $this->intermediateFilePaths['LP1'][0];
            return;
        }
        
        $pdf = PdfProcessor::getPdftkInstance();
        $intPdfHandle = 'A';
        if(isset($this->intermediateFilePaths['LP1'])) {
            $lastInsertion = 0;
            $pdf->addFile($this->intermediateFilePaths['LP1'], $intPdfHandle);
        }
        else {
            throw new \UnexpectedValueException('LP1 pdf was not generated before merging pdf intermediate files');
        }
        
        // Section 11 - additional attorneys signature
        if(isset($this->intermediateFilePaths['AdditionalAttorneySignature'])) {
            $insertAt = 15;
            $pdf->cat(++$lastInsertion, $insertAt, 'A');
            
            foreach($this->intermediateFilePaths['AdditionalAttorneySignature'] as $additionalAttorneySignature) {
                $pdf->addFile($additionalAttorneySignature, ++$intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle);
            }
            
            $lastInsertion = $insertAt;
        }
        
        // Section 12 additional applicants
        if(isset($this->intermediateFilePaths['AdditionalApplicant'])) {
            $insertAt = 17;
            $pdf->cat(++$lastInsertion, $insertAt, 'A');
            
            foreach($this->intermediateFilePaths['AdditionalApplicant'] as $additionalApplicant) {
                $pdf->addFile($additionalApplicant, ++$intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle);
            }
        
            $lastInsertion = $insertAt;
        }
        
        // Section 15 - additional applicants signature
        if(($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) &&
                is_array($this->lpa->document->whoIsRegistering) &&
                (count($this->lpa->document->whoIsRegistering) > 4)) {
                    $totalAdditionalApplicants = count($this->lpa->document->whoIsRegistering) - 4;
                    $totalAdditionalPages = ceil($totalAdditionalApplicants/4);
                    $insertAt = 20;
                    $pdf->cat(++$lastInsertion, $insertAt, 'A');
                    
                    for($i=0; $i<$totalAdditionalPages; $i++) {
                        $pdf->addFile($this->basePdfTemplatePath."/LP1_AdditionalApplicantSignature.pdf", ++$intPdfHandle);
                        $pdf->cat(1, null, $intPdfHandle);
                    }
                    
                    $lastInsertion = $insertAt;
        }
        
        // Continuation Sheet 1
        if(isset($this->intermediateFilePaths['CS1'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat(++$lastInsertion, $insertAt, 'A');
            }
            foreach ($this->intermediateFilePaths['CS1'] as $cs1) {
                $pdf->addFile($cs1, ++$intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle);
            }
            
            $lastInsertion = $insertAt;
        }
        
        // Continuation Sheet 2
        if(isset($this->intermediateFilePaths['CS2'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat(++$lastInsertion, $insertAt, 'A');
            }
            foreach ($this->intermediateFilePaths['CS2'] as $cs2) {
                $pdf->addFile($cs2, ++$intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle);
            }
            
            $lastInsertion = $insertAt;
        }
        
        // Continuation Sheet 3
        if(isset($this->intermediateFilePaths['CS3'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat(++$lastInsertion, $insertAt, 'A');
            }
            $pdf->addFile($this->intermediateFilePaths['CS3'], ++$intPdfHandle);
            $pdf->cat(1, null, $intPdfHandle);
            
            $lastInsertion = $insertAt;
        }
        
        // Continuation Sheet 4
        if(isset($this->intermediateFilePaths['CS4'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat(++$lastInsertion, $insertAt, 'A');
            }
            $pdf->addFile($this->intermediateFilePaths['CS4'], ++$intPdfHandle);
            $pdf->cat(1, null, $intPdfHandle);
            
            $lastInsertion = $insertAt;
        }
        
        if($lastInsertion < 20) {
            $pdf->cat($lastInsertion, 20, 'A');
        }
        
        $pdf->saveAs($this->generatedPdfFilePath);
//         print_r($pdf);
        
    } // function mergePdfs()
} // class Lp1