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
    const BOX_NO_OF_ROWS_CS2 = 17;
    
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
    
    static $CS1_SETTINGS = array(
        'max-slots-on-standard-form' => array(
            'primaryAttorneys'       => self::MAX_ATTORNEYS_ON_STANDARD_FORM,
            'replacementAttorneys'   => self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM,
            'peopleToNotify'        => self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM
        ),
        'max-slots-on-cs1-form' => 2
    );
    
    /**
     * bx - bottom x 
     * by - bottom y
     * tx - top x
     * ty - top y
     * @var array - stroke corrrdinates
     */
    protected $strokeParams = array(
        'primaryAttorney-1'      => array('bx'=>313, 'by'=>243, 'tx'=>550, 'ty'=>545),
        'primaryAttorney-2'      => array('bx'=>45,  'by'=>359, 'tx'=>283, 'ty'=>662),
        'primaryAttorney-3'      => array('bx'=>313, 'by'=>359, 'tx'=>550, 'ty'=>662),
        'replacementAttorney-0'  => array('bx'=>45,  'by'=>315, 'tx'=>283, 'ty'=>536),
        'replacementAttorney-1'  => array('bx'=>313, 'by'=>315, 'tx'=>550, 'ty'=>536),
        'life-sustain-A'         => array('bx'=>44,  'by'=>275, 'tx'=>283, 'ty'=>485),
        'life-sustain-B'         => array('bx'=>307, 'by'=>275, 'tx'=>550, 'ty'=>485),
        'people-to-notify-0'     => array('bx'=>44,  'by'=>335, 'tx'=>283, 'ty'=>501),
        'people-to-notify-1'     => array('bx'=>312, 'by'=>335, 'tx'=>552, 'ty'=>501),
        'people-to-notify-2'     => array('bx'=>44,  'by'=>127, 'tx'=>283, 'ty'=>294),
        'people-to-notify-3'     => array('bx'=>312, 'by'=>127, 'tx'=>552, 'ty'=>294),
        'preference'             => array('bx'=>41,  'by'=>439, 'tx'=>554, 'ty'=>529),
        'instruction'            => array('bx'=>41,  'by'=>157, 'tx'=>554, 'ty'=>248),
        'attorney-signature'     => array('bx'=>42,  'by'=>144, 'tx'=>553, 'ty'=>317),
        'applicant-0'            => array('bx'=>42,  'by'=>315, 'tx'=>283, 'ty'=>412),
        'applicant-1'            => array('bx'=>308, 'by'=>315, 'tx'=>549, 'ty'=>412),
        'applicant-2'            => array('bx'=>42,  'by'=>147, 'tx'=>283, 'ty'=>245),
        'applicant-3'            => array('bx'=>308, 'by'=>147, 'tx'=>549, 'ty'=>245),
        'additional-applicant-1' => array('bx'=>308, 'by'=>303, 'tx'=>549, 'ty'=>401),
        'additional-applicant-2' => array('bx'=>42,  'by'=>139, 'tx'=>283, 'ty'=>237),
        'additional-applicant-3' => array('bx'=>308, 'by'=>139, 'tx'=>549, 'ty'=>237),
        'cs1'                    => array('bx'=>313, 'by'=>262, 'tx'=>558, 'ty'=>645)
    );
    
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
        $mappings = $this->dataMappingForStandardForm();
        
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
            $this->intermediateFilePaths = array_merge($this->intermediateFilePaths, $generatedCs1);
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $this->addContinuationSheet1('replacementAttorneys');
        }
        
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > 4) {
            $this->addContinuationSheet1('peopleToNotify');
        }
        
        // generate a CS2 page if attorneys act depend on a special decision.
        if($this->lpa->document->primaryAttorneyDecisions->how == Decisions\PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $this->addContinuationSheet2('cs-2-is-decisions', $this->lpa->document->primaryAttorneyDecisions->howDetails);
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
            
            $this->addContinuationSheet2('cs-2-is-how-replacement-attorneys-step-in', $content);
        } // endif
        
        // generate a CS2 page if preference exceed available space on standard form.
        if(!$this->canFitIntoTextBox($this->lpa->document->preference)) {
            $this->addContinuationSheet2('cs-2-is-preferences', $this->lpa->document->preference);
        }
        
        // generate a CS2 page if instruction exceed available space on standard form.
        if(!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
            $this->addContinuationSheet2('cs-2-is-instructions', $this->lpa->document->instruction);
        }
        
        // generate CS3 page if donor cannot sign on LPA
        if(false === $this->lpa->document->donor->canSign) {
            $this->addContinuationSheet3();
        }
        
        // if number of attorneys (including replacements) is greater than 4, duplicate 
        // Section 11 - Attorneys Signatures as many as needed to be able to fit all attorneys in the form.
        if(count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys) > 4) {
            $this->addAdditionalAttorneySignaturePages();
        }
        
        // if number of applicant is greater than 4, duplicate Section 12 - Applicants 
        // as many as needed to be able to fit all applicants in.
        if(is_array($this->lpa->document->whoIsRegistering) && (count($this->lpa->document->whoIsRegistering)>4)) {
            $this->addAdditionalApplicantPages();
        }
        
    } // function generateAdditionalPagePdfs()
    
    /**
     * 
     * @param string $type - cs-2-is-decisions || cs-2-is-how-replacement-attorneys-step-in || cs-2-is-preferences || cs-2-is-instructions
     * @param string $content
     */
    protected function addContinuationSheet2($type, $content)
    {
        $formatedContentLength = strlen($this->flattenTextContent($content));
        if(($type == 'cs-2-is-decisions') || ($type == 'cs-2-is-how-replacement-attorneys-step-in')) {
            $contentLengthOnStandardForm = 0;
            $totalAdditionalPages = ceil($formatedContentLength/(self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS_CS2));
        }
        else {
            $contentLengthOnStandardForm = self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS;
            $totalAdditionalPages = ceil(($formatedContentLength-$contentLengthOnStandardForm)/(self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS_CS2));
        }
        
        for($i=1; $i<=$totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');
            
            $cs2 = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LPC_Continuation_Sheet_2.pdf");
            
            $cs2->fillForm(array(
                    $type               => self::CHECK_BOX_ON,
                    'cs-2-content'      => $this->getContentForBox($i, $content, true),
                    'donor-full-name'   => $this->fullName($this->lpa->document->donor->name)
            ))->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($cs2);
        }
    } //  function addContinuationSheet2($type, $content)
    
    /**
     * Fill the donor's full name only.
     */
    protected function addContinuationSheet3()
    {
        $filePath = $this->registerTempFile('CS3');
    
        $cs3 = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LPC_Continuation_Sheet_3.pdf");
    
        $cs3->fillForm(array(
                'donor-full-name' => $this->fullName($this->lpa->document->donor->name)
        ))->needAppearances()
            ->flatten()
            ->saveAs($filePath);
//         print_r($cs3);
        
    } //  function addContinuationSheet3()
    
    
    /**
     * Duplicate Section 11 page for additional primary and replacement attorneys to sign
     */
    protected function addAdditionalAttorneySignaturePages()
    {
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        
        $skipped=0;
        foreach($allAttorneys as $attorney) {
            
            // skip trust corp
            if($attorney instanceof TrustCorporation) continue;
            
            // skip first 4 human attorneys
            $skipped++;
            if($skipped <= self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) continue;
            
            $filePath = $this->registerTempFile('AdditionalAttorneySignature');
            
            $attorneySignaturePage = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LP1_AdditionalAttorneySignature.pdf");
            $attorneySignaturePage->fillForm(array(
                    'signature-attorney-name-title' => $attorney->name->title,
                    'signature-attorney-name-first' => $attorney->name->first,
                    'signature-attorney-name-last'  => $attorney->name->last
            ))->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($attorneySignaturePage);
            
        } //endforeach
    } // function addAdditionalAttorneySignaturePages()
    
    /**
     * Duplicate Section 12 page for additional applicants 
     */
    protected function addAdditionalApplicantPages()
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
        
    } // function addAdditionalApplicantPages()
    
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
        
    } // function combinePdfs()
    
    protected function dataMappingForStandardForm()
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
            $this->flattenLpa['only-one-attorney-appointed'] = self::CHECK_BOX_ON;
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
        
        // populate attorney dob
        for($i=0; $i<$noOfPrimaryAttorneys; $i++) {
            if($this->lpa->document->primaryAttorneys[$i] instanceof TrustCorporation) continue;
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('d');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('m');
            $this->flattenLpa['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $this->lpa->document->primaryAttorneys[$i]->dob->date->format('Y');
            if($i==3) break;
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
        
        /**
         * replacement attorneys section (section 4)
         */
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
        if($noOfPeopleToNotify > 4) {
            $this->flattenLpa['has-more-than-5-notified-people'] = self::CHECK_BOX_ON;
        }
        else {
            switch($noOfPeopleToNotify) {
                case 3:
                    $this->drawingTargets[6] = array('people-to-notify-3');
                    break;
                case 2:
                    $this->drawingTargets[6] = array('people-to-notify-3','people-to-notify-2');
                    break;
                case 1:
                    $this->drawingTargets[6] = array('people-to-notify-3','people-to-notify-2','people-to-notify-1');
                    break;
                case 0:
                    $this->drawingTargets[6] = array('people-to-notify-3','people-to-notify-2','people-to-notify-1','people-to-notify-0');
                    break;
            }
        }
        
        /**
         *  Preference and Instructions. (Section 7)
         */
        if(empty($this->flattenLpa['lpa-document-preference'])) {
            $this->drawingTargets[7] = array('preference');
        }
        else {
            $this->flattenLpa['lpa-document-preference'] = $this->getContentForBox(0, $this->flattenLpa['lpa-document-preference'], false);
        }
        
        if(empty($this->flattenLpa['lpa-document-instruction'])) {
            $this->drawingTargets[7] = isset($this->drawingTargets[7])? array('preference', 'instruction'):array('instruction');
        }
        else {
            $this->flattenLpa['lpa-document-instruction'] = $this->getContentForBox(0, $this->flattenLpa['lpa-document-instruction'], false);
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
                    $this->flattenLpa['applicant-'.$index.'-name-last']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name'];
                }
                else {
                    $this->flattenLpa['applicant-'.$index.'-name-title']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-title'];
                    $this->flattenLpa['applicant-'.$index.'-name-first']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-first'];
                    $this->flattenLpa['applicant-'.$index.'-name-last']     = $this->flattenLpa['lpa-document-primaryAttorneys-'.$attorneyId.'-name-last'];
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
        
    } // function modelPdfFieldDataMapping()
    
    /**
     * Convert all new lines with spaces to fill out to the end of each line
     * 
     * @param string $content
     * @return string
     */
    private function flattenTextContent($content)
    {
        // strip space & new lines chars at both ends.
        $content = trim($content);
        
        $paragraphs = explode("\n", $content);
        foreach($paragraphs as &$paragraph) {
            $paragraph = trim($paragraph);
            if(strlen($paragraph == 0)) {
                $paragraph = str_repeat(" ", self::BOX_CHARS_PER_ROW);
            }
            else {
                // calculate how many space chars to be appended to replace the new line in this paragraph.
                $noOfSpaces = self::BOX_CHARS_PER_ROW - strlen($paragraph) % self::BOX_CHARS_PER_ROW;
                if($noOfSpaces > 0) {
                    $paragraph .= str_repeat(" ", $noOfSpaces);
                }
            }
        }
        
        return implode("", $paragraphs);
    } // function flattenBoxContent($content)
    
    /**
     * Get content for a multiline text box.
     * @param int $pageNo - start from 1 for continuation sheets. 0 for Preferences and instructions in Section 7 box.
     * @param string $content - user input content for preference/instruction/decisions/step-in
     * @param bool $isContinuationSheet.
     * @return string|NULL
     */
    private function getContentForBox($pageNo, $content, $isContinuationSheet)
    {
        $flattenContent = $this->flattenTextContent($content);
        
        // return content for preference or instruction in section 7.
        if(!$isContinuationSheet) {
            return "\n".substr($flattenContent, 0, self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS);
        }
        
        $chunks = str_split(substr($flattenContent, self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS), self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS_CS2+1);
        if(isset($chunks[$pageNo-1])) {
            return "\n".$chunks[$pageNo-1];
        }
        else {
            return null;
        }
    } // function getContentForBox($pageNo, $content, $isContinuationSheet)
    
    /**
     * Check if the text content can fit into the text box in the Section 7 page in the base PDF form.
     * 
     * @return boolean
     */
    private function canFitIntoTextBox($content)
    {
        $flattenContent = $this->flattenTextContent($content);
        return strlen($flattenContent) <= self::BOX_CHARS_PER_ROW*self::BOX_NO_OF_ROWS;
    } // function canFitIntoTextBox()
    
} // class Lp1