<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use mikehaertl\pdftk\pdf as Pdf;

abstract class Lp1 extends AbstractForm
{
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return Form object
     */
    public function generate()
    {
//         $this->generateDefaultPdf();
        
        if($this->hasAdditionalPages()) {
            
            $this->generateAdditionalPagePdfs();
            print_r($this->intermediatePdfFilePaths);
            $this->combinePdfs();
        }
        
        return $this;
        
    } // function generate()
    
    /**
     * Populate LP1F/H base template PDF and generate as tmeporary pdf for merging additional pages if needed to.
     */
    protected function generateDefaultPdf()
    {
        $this->intermediatePdfFilePaths['lp1'] = '/tmp/pdf-LP1-'.$this->lpa->id.'-'.microtime().'.pdf';
        
        $flattenLpaData = $this->mapData();
        
        $this->pdf->fillForm($flattenLpaData)
            ->needAppearances()
            ->saveAs($this->intermediatePdfFilePaths['lp1']);
        
    } // function generateDefaultPdf()
    
    /**
     * 
     * @return boolean
     */
    protected function hasAdditionalPages()
    {
        // if there are additional attorneys, replacements or notifed people - CS1
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if(($noOfPrimaryAttorneys > 4) || ($noOfReplacementAttorneys > 2) || ($noOfPeopleToNotify > 4)) {
            return true;
        }
        
        // if there's attorneys act decisions - CS2
        if($this->lpa->document->decisions->primaryAttorneys->how == Decisions::LPA_DECISION_HOW_MIXED) {
            return true;
        } 
        
        // if there's replacement attorney steps in - CS2
        if($this->lpa->document->replacementAttorneys->how == 'step-in') {
            return true;
        }
        
        // if need more space in preferences - CS2
        if($this->canFitIntoTextBox($this->lpa->document->preference)) {
            return true;
        }
        
        // if need more space in instructions - CS2
        if($this->canFitIntoTextBox($this->lpa->document->instruction)) {
            return true;
        }
        
        // if donor cannot sign - CS3
        if(false === $this->lpa->document->donor->canSign) {
            return true;
        }
        
    } // function hasAdditionalPages()
    
    protected function generateAdditionalPagePdfs()
    {
        // CS1
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys > 4) {
            $this->addContinuationSheet1('primaryAttorneys', 4);
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
            if($noOfReplacementAttorneys > 4) {
            $this->addContinuationSheet1('replacementAttorneys', 2);
        }
        
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > 4) {
            $this->addContinuationSheet1('peopleToNotify', 4);
        }
        
        // CS2 @todo - wait for model to be done
//         if($this->lpa->document->decisions->primaryAttorneys->how == Decisions::LPA_DECISION_HOW_MIXED) {
//             $this->addContinuationSheet2('cs-2-is-decisions', $this->lpa->document->decisions->details);
//         }
        
//         if($this->lpa->document->decisions->primaryAttorneys->how == Decisions::LPA_DECISION_HOW_MIXED) {
//             $this->addContinuationSheet2('cs-2-is-how-replacement-attorneys-step-in', $this->lpa->document->decisions->details);
//         }
        
        if(!$this->canFitIntoTextBox($this->lpa->document->preference)) {
            $this->addContinuationSheet2('cs-2-is-preferences', $this->lpa->document->decisions->details);
        }
        
        if(!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
            $this->addContinuationSheet2('cs-2-is-instructions', $this->lpa->document->instruction->details);
        }
        
        // CS3
        if(false === $this->lpa->document->donor->canSign) {
            $this->addContinuationSheet3();
        }
        
        // Section 11 - Attorneys Signatures
        if(count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys) > 4) {
            $this->addAdditionalAttorneySignaturePages();
        }
        
        // Section 12 - Applicants
        if(is_array($this->lpa->document->whoIsRegistering) && (count($this->lpa->document->whoIsRegistering)>4)) {
            $this->addAdditionalApplicantPages();
        }
        
    } // function generateAdditionalPagePdfs()
    
    /**
     * 
     * @param string $type - primaryAttorneys, replacementAttorneys or peopleToNotify.
     * @param int $limitOnLp1 - number of persons that have populated on LP1F/H. Eg. 4 primary attorneys.
     */
    private function addContinuationSheet1($type, $limitOnLp1)
    {
        $total = count($this->lpa->document->{$type});
        $totalAdditionals = $total - $limitOnLp1;
        $noOfAdditionalPages = ceil($totalAdditionals/2);
        
        $this->intermediatePdfFilePaths['cs1'] = array();
        
        $totalMappedAdditionalPeople = 0;
        for($i=0; $i<$noOfAdditionalPages; $i++) {
            
            $tmpSavePath = '/tmp/pdf-CS1-'.$this->lpa->id.'-'.microtime().'.pdf';
            $this->intermediatePdfFilePaths['cs1'][] = $tmpSavePath;
            
            $cs1 = new Pdf('../assets/v2/LPC_Continuation_Sheet_1.pdf');
            
            for($j=0; $j<2; $j++) {
                
                $formData = array(
                        'cs1-'.$j.'-is-attorney'=>self::CHECK_BOX_ON,
                        'cs1-'.$j.'-name-title'       => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-name-title'],
                        'cs1-'.$j.'-name-first'       => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-name-first'],
                        'cs1-'.$j.'-name-last'        => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-name-last'],
                        
                        'cs1-'.$j.'-address-address1' => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-address-address1'],
                        'cs1-'.$j.'-address-address2' => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-address-address2'],
                        'cs1-'.$j.'-address-address3' => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-address-address3'],
                        'cs1-'.$j.'-address-postode'  => $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-address-postcode']
        
                );
                
                if($type != 'peopleToNotify') {
                    $formData['cs1-'.$j.'-dob-date-day']   = $this->lpa->document->{$type}[($i*2+$j+4)]->dob->date->format('d');
                    $formData['cs1-'.$j.'-dob-date-month'] = $this->lpa->document->{$type}[($i*2+$j+4)]->dob->date->format('m');
                    $formData['cs1-'.$j.'-dob-date-year']  = $this->lpa->document->{$type}[($i*2+$j+4)]->dob->date->format('Y');
                    $formData['cs1-'.$j.'-email-address']  = $this->flattenLpa['lpa-document-'.$type.'-'.($i*2+$j+4).'-email-address'];
                }
                
                $formData['cs1-donor-full-name'] = $this->fullName($this->lpa->document->donor);
                
                if(++$totalMappedAdditionalPeople > $totalAdditionals) break 2;
                
            } // loop for 2 persons per page

            $cs1->fillForm($formData)
            ->needAppearances()
            ->saveAs($tmpSavePath);
            
        } // loop each CS page
        
    } // function addContinuationSheet()
    
    /**
     * 
     * @param string $type - cs-2-is-decisions || cs-2-is-how-replacement-attorneys-step-in || cs-2-is-preferences || cs-2-is-instructions
     * @param string $content
     */
    protected function addContinuationSheet2($type, $content)
    {
        $tmpSavePath = '/tmp/pdf-CS2-'.$this->lpa->id.'-'.microtime().'.pdf';
        $this->intermediatePdfFilePaths['cs2'][] = $tmpSavePath;
        
        // @todo calculate no. of pages to be added
        
        $cs2 = new Pdf('../assets/v2/LPC_Continuation_Sheet_2.pdf');
        
        $cs2->fillForm(array(
                $type => 'On',
                'cs-2-content' => $content,
                'donor-full-name' => $this->fullName($this->lpa->document->donor)
        ))->needAppearances()
          ->saveAs($tmpSavePath);
        
        
    } //  function addContinuationSheet2($type, $content)
    
    /**
     * Fill the donor's full name only.
     */
    protected function addContinuationSheet3()
    {
        $tmpSavePath = '/tmp/pdf-CS3-'.$this->lpa->id.'-'.microtime().'.pdf';
        $this->intermediatePdfFilePaths['cs3'] = $tmpSavePath;
    
        $cs2 = new Pdf('../assets/v2/LPC_Continuation_Sheet_3.pdf');
    
        $cs2->fillForm(array(
                'donor-full-name' => $this->fullName($this->lpa->document->donor)
        ))->needAppearances()
        ->saveAs($tmpSavePath);
        
    } //  function addContinuationSheet3()
    
    
    /**
     * Duplicate Section 11 page for primary and replacement attorneys to sign
     */
    protected function addAdditionalAttorneySignaturePages()
    {
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $i=0;
        foreach($allAttorneys as $attorney) {
            
            if($attorney instanceof TrustCorporation) continue;
            
            $i++;
            if($i <= 4) continue;
            
            $tmpSavePath = '/tmp/pdf-AdditionalAttorneySignature-'.$this->lpa->id.'-'.microtime().'.pdf';
            $this->intermediatePdfFilePaths['AdditionalAttorneySignature'][] = $tmpSavePath;
            
            $attorneySignaturePage = new Pdf('../assets/v2/AdditionalAttorneySignature.pdf');
            $attorneySignaturePage->fillForm(array(
                    'signature-attorney-name-title' => $attorney->name->title,
                    'signature-attorney-name-first' => $attorney->name->first,
                    'signature-attorney-name-last'  => $attorney->name->last
            ))->needAppearances()
            ->saveAs($tmpSavePath);
        }
    } // function addAdditionalAttorneySignaturePages()
    
    protected function addAdditionalApplicantPages()
    {
        $i = 0;
        $totalApplicant = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicant = $totalApplicant - 4;
        $totalAdditionalPages = ceil($totalAdditionalApplicant/4);
        
        $totalMappedAdditionalApplicants = 0;
        for($i=0; $i<$totalAdditionalPages; $i++) {
            $tmpSavePath = '/tmp/pdf-AdditionalApplicant-'.$this->lpa->id.'-'.microtime().'.pdf';
            $this->intermediatePdfFilePaths['AdditionalApplicant'][] = $tmpSavePath;
            
            $additionalApplicant = new Pdf('../assets/v2/AdditionalApplicant.pdf');
            
            for($j=0; $j<4; $j++) {
                
                $attorneyId = $this->lpa->document->whoIsRegistering[$i*4+$j+4];
                $formData = array(
                        'applicant-'.$j.'-name-title'  => $this->lpa->document->primaryAttorneys[$attorneyId]->name->title,
                        'applicant-'.$j.'-name-first'  => $this->lpa->document->primaryAttorneys[$attorneyId]->name->first,
                        'applicant-'.$j.'-name-last'   => $this->lpa->document->primaryAttorneys[$attorneyId]->name->last,
                        'applicant-'.$j.'-dob-date-day' => $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('d'),
                        'applicant-'.$j.'-dob-date-month' => $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('m'),
                        'applicant-'.$j.'-dob-date-year' => $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('Y'),
                );
                
                if(++$totalMappedAdditionalApplicants > $totalAdditionalApplicant) break 2;
            }
        }
        
        foreach($this->lpa->document->whoIsRegistering as $attorneyId) {
            $i++;
            if($i <= 4) continue;
            
            
            $attorneySignaturePage = new Pdf('../assets/v2/AdditionalApplicant.pdf');
            $attorneySignaturePage->fillForm(array(
                    'applicant-0-name-title' => $this->lpa->document->primaryAttorneys[$attorneyId]->name->title,
                    'signature-attorney-name-first' => $this->lpa->document->primaryAttorneys[$attorneyId]->name->first,
                    'signature-attorney-name-last'  => $this->lpa->document->primaryAttorneys[$attorneyId]->name->last
            ))->needAppearances()
            ->saveAs($tmpSavePath);
        }
    } // function addAdditionalApplicantPages()
    
    protected function combinePdfs()
    {
        // Section 15 - Applicant signature
        if(($this->lpa->document->decisions->how == Decisions::LPA_DECISION_HOW_JOINTLY) &&
                is_array($this->lpa->document->whoIsRegistering) &&
                (count($this->lpa->document->whoIsRegistering) > 4)) {
                    $this->addAdditionalApplicantSignaturePages();
                }
    } // function combinePdfs()
    
    protected function mapData()
    {
        $this->flattenLpa['lpa-id'] = Formatter::id($this->lpa->id);
        
        $this->flattenLpa['lpa-document-donor-dob-date-day'] =  $this->lpa->document->donor->dob->date->format('d');
        $this->flattenLpa['lpa-document-donor-dob-date-month'] = $this->lpa->document->donor->dob->date->format('m');
        $this->flattenLpa['lpa-document-donor-dob-date-year'] = $this->lpa->document->donor->dob->date->format('Y');
        
        // attorneys section (section 2)
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->flattenLpa['only-one-attorney-appointed'] = self::CHECK_BOX_ON;
        }
        elseif($noOfPrimaryAttorneys > 4) {
            $this->flattenLpa['has-more-than-4-attorneys'] = self::CHECK_BOX_ON;
        }
        
        // populate attorney dob
        for($i=0; $i<$noOfPrimaryAttorneys; +$i++) {
            if($this->lpa->document->primaryAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('Y');
            if($i==3) break;
        }
        
        // attorney decision section (section 3)
        if($noOfPrimaryAttorneys > 1) {
            switch($this->flattenLpa['lpa-document-decisions-how']) {
                case Decisions::LPA_DECISION_HOW_JOINTLY:
                    $this->flattenLpa['attorneys-act-jointly'] = self::CHECK_BOX_ON;
                    break;
                case Decisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $this->flattenLpa['attorneys-act-jointly-and-severally'] = self::CHECK_BOX_ON;
                    break;
                case Decisions::LPA_DECISION_HOW_MIXED:
                    $this->flattenLpa['attorneys-act-upon-decisions'] = self::CHECK_BOX_ON;
                    
                    //@todo fill CS2
                    break;
                default:
                    break;
            }
        }
        
        // replacement attorneys section (section 4)
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $this->flattenLpa['has-more-than-2-replacement-attorneys'] = self::CHECK_BOX_ON;
        }
        
        /**
         * populate replacement attorney dob (section 4)
         */
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            if($this->lpa->document->replacementAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('Y');
            if($i==1) break;
        }
        
        
        /**
         * People to notify (Section 6)
         */
        if(count($this->lpa->document->peopleToNotify) > 4) {
            $this->flattenLpa['has-more-than-5-notified-people'] = self::CHECK_BOX_ON;
        }
        
        /**
         *  @todo: calculate characters in Preference and Instructions boxes and split to CS2. (Section 7)
         */
        
        
        /**
         * Populate primary and replacement attorneys signature pages (Section 11)
         */
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $i=0;
        foreach($allAttorneys as $attorney) {
            
            if($attorney instanceof TrustCorporation) continue;
            
            $this->flattenLpa['signature-attorney-'.$i.'-name-title'] = $attorney->name->title;
            $this->flattenLpa['signature-attorney-'.$i.'-name-first'] = $attorney->name->first;
            $this->flattenLpa['signature-attorney-'.$i.'-name-last'] = $attorney->name->last;
            $i++;
            
            // @todo dup section 11 pages
        }
        
        
        /**
         * Applicant (Section 12)
         */
        if($this->flattenLpa['lpa-document-whoIsRegistering'] == 'donor') {
            $this->flattenLpa['donor-is-applicant'] = self::CHECK_BOX_ON;
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            foreach($this->lpa->document->whoIsRegistering as $attorneyId) {
                $this->flattenLpa['applicant-'.$attorneyId.'-name-title'] = $this->flattenLpa['lpa-document-primaryAttorneys'.$attorneyId.'-name-title'];
                $this->flattenLpa['applicant-'.$attorneyId.'-name-first'] = $this->flattenLpa['lpa-document-primaryAttorneys'.$attorneyId.'-name-first'];
                $this->flattenLpa['applicant-'.$attorneyId.'-name-first'] = $this->flattenLpa['lpa-document-primaryAttorneys'.$attorneyId.'-name-last'];
                $this->flattenLpa['applicant-'.$attorneyId.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('d');
                $this->flattenLpa['applicant-'.$attorneyId.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('m');
                $this->flattenLpa['applicant-'.$attorneyId.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('Y');
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
        }
        elseif($this->flattenLpa['lpa-payment-method'] == Payment::PAYMENT_TYPE_CHEQUE) {
            $this->flattenLpa['pay-by-cheque'] = self::CHECK_BOX_ON;
        }
        
        
        // @todo: Fee reduction, repeat application
        
        
        
        // Online payment details
        if(isset($this->flattenLpa['lpa-payment-reference'])) {
            $this->flattenLpa['lpa-payment-amount'] = '£'.sprintf('%.2f', $this->flattenLpa['lpa-payment-amount']);
            $this->flattenLpa['lpa-payment-date-day'] = $this->lpa->payment->date->format('d');
            $this->flattenLpa['lpa-payment-date-month'] = $this->lpa->payment->date->format('m');
            $this->flattenLpa['lpa-payment-date-year'] = $this->lpa->payment->date->format('Y');
        }
        
    } // function mapData()
    
    /**
     * Check if the text content can fit into the text box in PDF form.
     * 
     * @todo to be implemented
     * @return boolean
     */
    private function canFitIntoTextBox()
    {
        return true;
    } // function canFitIntoTextBox()
    
} // class Lp1