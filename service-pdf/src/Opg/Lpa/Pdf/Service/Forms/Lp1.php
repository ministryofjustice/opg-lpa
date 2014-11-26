<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use mikehaertl\pdftk\pdf as Pdf;

define("PDF_TEMPLATE_PATH",  __DIR__."/../../../../../../assets/v2");

abstract class Lp1 extends AbstractForm
{
    const BOX_ROW_LENGTH=84;
    const BOX_COLUMN_LENGTH=8;
    
    
    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     *
     * @return Form object
     */
    public function generate()
    {
        $this->generateDefaultPdf();
        
        if($this->hasAdditionalPages()) {
            
            $this->generateAdditionalPagePdfs();
            $this->mergePdfs();
        }
        
        return $this;
        
    } // function generate()
    
    /**
     * Populate LP1F/H base template PDF and generate as tmeporary pdf for merging additional pages if needed to.
     */
    protected function generateDefaultPdf()
    {
        $this->intermediatePdfFilePaths['LP1'] = '/tmp/pdf-LP1-'.$this->lpa->id.'-'.microtime().'.pdf';
        
        $this->mapData();
        $this->pdf->fillForm($this->flattenLpa)
            ->needAppearances()
            ->saveAs($this->intermediatePdfFilePaths['LP1']);
        
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
        if($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            return true;
        }
        
        // if there's replacement attorney steps in - CS2
        if( (count($this->lpa->document->replacementAttorneys) > 1)
            && ($this->lpa->document->replacementAttorneys->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
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
        
        if($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $content = $this->lpa->document->primaryAttorneyDecisions->howDetails;
            $this->addContinuationSheet2('cs-2-is-decisions', $content);
        }
        
        if($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
            // @todo - investigate what need to include into the $content
            $content = $this->lpa->document->replacementAttorneyDecisions->howDetails;
            $this->addContinuationSheet2('cs-2-is-how-replacement-attorneys-step-in', $content);
        }
        
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
        
        $this->intermediatePdfFilePaths['CS1'] = array();
        
        $totalMappedAdditionalPeople = 0;
        for($i=0; $i<$noOfAdditionalPages; $i++) {
            
            $tmpSavePath = '/tmp/pdf-CS1-'.$this->lpa->id.'-'.microtime().'.pdf';
            $this->intermediatePdfFilePaths['CS1'][] = $tmpSavePath;
            
            $cs1 = new Pdf(PDF_TEMPLATE_PATH."/LPC_Continuation_Sheet_1.pdf");
            
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
                
                if(++$totalMappedAdditionalPeople >= $totalAdditionals) {
                    break;
                }
                
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
        $this->intermediatePdfFilePaths['CS2'][] = $tmpSavePath;
        
        // @todo calculate no. of pages to be added based on the length of $content and number of new line chars in it.
        
        $cs2 = new Pdf(PDF_TEMPLATE_PATH."/LPC_Continuation_Sheet_2.pdf");
        
        $cs2->fillForm(array(
                $type => self::CHECK_BOX_ON,
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
        $this->intermediatePdfFilePaths['CS3'] = $tmpSavePath;
    
        $cs2 = new Pdf(PDF_TEMPLATE_PATH."/LPC_Continuation_Sheet_3.pdf");
    
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
        $i=0;
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        foreach($allAttorneys as $attorney) {
            
            if($attorney instanceof TrustCorporation) continue;
            
            $i++;
            if($i <= 4) continue;
            
            $tmpSavePath = '/tmp/pdf-AdditionalAttorneySignature-'.$this->lpa->id.'-'.microtime().'.pdf';
            $this->intermediatePdfFilePaths['AdditionalAttorneySignature'][] = $tmpSavePath;
            
            $attorneySignaturePage = new Pdf(PDF_TEMPLATE_PATH."/AdditionalAttorneySignature.pdf");
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
            
            $additionalApplicant = new Pdf(PDF_TEMPLATE_PATH."/AdditionalApplicant.pdf");
            
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
            
            $formData['attorney-is-applicant'] = self::CHECK_BOX_ON;
            
            $additionalApplicant->fillForm($formData)
                ->needAppearances()
                ->saveAs($tmpSavePath);
        }
        
    } // function addAdditionalApplicantPages()
    
    protected function mergePdfs()
    {
        if((count($this->intermediatePdfFilePaths) == 1) && isset($this->intermediatePdfFilePaths['LP1'])) {
            $this->generatedPdfFilePath = $this->intermediatePdfFilePaths['LP1'];
            return;
        }
        
        $pdf = new Pdf();
        $intPdfHandle = 'A';
        if(isset($this->intermediatePdfFilePaths['LP1'])) {
            $lastInsertion = 1;
            $pdf->addFile($this->intermediatePdfFilePaths['LP1'], $intPdfHandle);
        }
        else {
            throw new \UnexpectedValueException('LP1 pdf was not generated before merging pdf intermediate files');
        }
        
        if(isset($this->intermediatePdfFilePaths['AdditionalAttorneySignature'])) {
            $insertAt = 15;
            $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            foreach($this->intermediatePdfFilePaths['AdditionalAttorneySignature'] as $additionalAttorneySignature) {
                $pdf->addFile($additionalAttorneySignature, $intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle++);
            }
            
            $lastInsertion = $insertAt;
        }
        
        if(isset($this->intermediatePdfFilePaths['AdditionalApplicant'])) {
            $insertAt = 17;
            $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            foreach($this->intermediatePdfFilePaths['AdditionalApplicant'] as $additionalApplicant) {
                $pdf->addFile($additionalApplicant, $intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle++);
            }
        
            $lastInsertion = $insertAt;
        }
        
        // Section 15 - Applicant signature
        if(($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) &&
                is_array($this->lpa->document->whoIsRegistering) &&
                (count($this->lpa->document->whoIsRegistering) > 4)) {
                    $totalAdditionalApplicants = count($this->lpa->document->whoIsRegistering) - 4;
                    $totalAdditionalPages = ceil($totalAdditionalApplicants/4);
                    $insertAt = 20;
                    $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
                    for($i=0; $i<$totalAdditionalPages; $i++) {
                        $pdf->addFile(PDF_TEMPLATE_PATH."/AdditionalApplicantSignature.pdf", $intPdfHandle);
                        $pdf->cat(1, null, $intPdfHandle++);
                    }
                    
                    $lastInsertion = $insertAt;
        }
        
        if(isset($this->intermediatePdfFilePaths['CS1'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            }
            foreach ($this->intermediatePdfFilePaths['CS1'] as $cs1) {
                $pdf->addFile($cs1, $intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle++);
            }
            
            $lastInsertion = $insertAt;
        }
        
        if(isset($this->intermediatePdfFilePaths['CS2'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            }
            foreach ($this->intermediatePdfFilePaths['CS2'] as $cs2) {
                $pdf->addFile($cs2, $intPdfHandle);
                $pdf->cat(1, null, $intPdfHandle++);
            }
            
            $lastInsertion = $insertAt;
        }
        
        if(isset($this->intermediatePdfFilePaths['CS3'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            }
            $pdf->addFile($this->intermediatePdfFilePaths['CS3'], $intPdfHandle);
            $pdf->cat(1, null, $intPdfHandle++);
            
            $lastInsertion = $insertAt;
        }
        
        if(isset($this->intermediatePdfFilePaths['CS4'])) {
            $insertAt = 20;
            if($lastInsertion != $insertAt) {
                $pdf->cat($lastInsertion, $insertAt, $intPdfHandle++);
            }
            $pdf->addFile($this->intermediatePdfFilePaths['CS4'], $intPdfHandle);
            $pdf->cat(1, null, $intPdfHandle++);
            
            $lastInsertion = $insertAt;
        }
        
        $pdf->saveAs($this->generatedPdfFilePath);
        
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
        
        // replacement attorneys section (section 4)
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        for($i=0; $i<$noOfReplacementAttorneys; +$i++) {
            if($this->lpa->document->replacementAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-replacementAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->replacementAttorneys[$i]->dob->date->format('Y');
            if($i==1) break;
        }
        
        if($noOfReplacementAttorneys > 2) {
            $this->flattenLpa['has-more-than-2-replacement-attorneys'] = self::CHECK_BOX_ON;
        }
        
        if(($noOfReplacementAttorneys > 1) &&
            ($this->lpa->document->replacementAttorneyDecisions->how != Decisions\ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                $this->flattenLpa['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
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
        }
        
        
        /**
         * Applicant (Section 12)
         */
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->flattenLpa['donor-is-applicant'] = self::CHECK_BOX_ON;
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            $this->flattenLpa['attorney-is-applicant'] = self::CHECK_BOX_ON;
            foreach($this->lpa->document->whoIsRegistering as $index=>$attorneyId) {
                $this->flattenLpa['applicant-'.$index.'-name-title']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-title'];
                $this->flattenLpa['applicant-'.$index.'-name-first']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-first'];
                $this->flattenLpa['applicant-'.$index.'-name-last']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-last'];
                $this->flattenLpa['applicant-'.$index.'-dob-date-day']   = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('d');
                $this->flattenLpa['applicant-'.$index.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('m');
                $this->flattenLpa['applicant-'.$index.'-dob-date-year']  = $this->lpa->document->primaryAttorneys[$attorneyId]->dob->date->format('Y');
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
     * @todo replace new line chars with spaces and calculate content length
     * @return boolean
     */
    private function canFitIntoTextBox()
    {
        return true;
    } // function canFitIntoTextBox()
    
} // class Lp1