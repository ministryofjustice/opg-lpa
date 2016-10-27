<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\Pdf\Logger\Logger;
use Opg\Lpa\Pdf\Service\PdftkInstance;
use mikehaertl\pdftk\Pdf;

use Zend\Barcode\Barcode;

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
     * @var bool There or not the registration section of teh LPA is complete.
     */
    private $registrationIsComplete;
    
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
        
        $stateChecker = new StateChecker($lpa);
        
        $this->registrationIsComplete = $stateChecker->isStateCompleted();

    }
    
    /**
     * Populate LPA data into PDF forms, generate pdf file.
     *
     * @return Form object
     */
    public function generate()
    {
        Logger::getInstance()->info(
            'Generating Lp1',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        $this->generateStandardForm();
        $this->generateAdditionalPages();
        $this->generateCoversheets();
        $this->mergePdfs();
        $this->protectPdf();
        
        return $this;
        
    } // function generate()
    
    /**
     * Populate LP1F/H base template PDF and generate as tmeporary pdf for merging additional pages if needed to.
     */
    protected function generateStandardForm()
    {
        Logger::getInstance()->info(
            'Generating Standard Form',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        // register a random generated temp file path, and store it $interFileStack.
        $filePath = $this->registerTempFile('LP1');
        
        // data mapping
        $mappings = $this->dataMapping();
        
        // populate form data and generate pdf
        $this->pdf->fillForm($mappings)
            ->flatten()
            ->saveAs($filePath);

        //---

        // If registration is complete add the tracking barcode.
        if( $this->registrationIsComplete ){
            $this->addLpaIdBarcode( $filePath );
        }

        //---
        
        // draw cross lines if there's any blank slot
        if(!empty($this->drawingTargets)) {
            $this->drawCrossLines($filePath, $this->drawingTargets);
        }
        
    } // function generateDefaultPdf()

    protected function addLpaIdBarcode( $filePath ){

        //------------------------------------------
        // Generate the barcode

        // Zero pad the ID, and prepend the 'A'.
        $formattedLpaId = 'A'.sprintf("%011d", $this->lpa->id);

        $renderer = Barcode::factory(
            'code39',
            'pdf',
            [
                'text' => $formattedLpaId,
                'drawText' => false,
                'factor' => 2,
                'barHeight' => 25,
            ],
            [
                'topOffset' => 789,
                'leftOffset' => 40,
            ]
        );

        $imageResource = $renderer->draw();

        $barcodeTmpFile = $this->getTmpFilePath('barcode');

        // Save to temporary file...
        $imageResource->save( $barcodeTmpFile );


        //------------------------------------------
        // Merge the barcode into the page


        // Take a copy of the PDF to work with.
        $pdfWithBarcode = PdftkInstance::getInstance( $filePath );

        // Pull out the page the barcode is appended to.
        $pdfWithBarcode->cat(19);


        // Add the barcode to the page.
        $pdfWithBarcode = new Pdf($pdfWithBarcode);
        $pdfWithBarcode->stamp( $barcodeTmpFile );


        //------------------------------------------
        // Re-integrate the page into the full PDF.

        $pdf = new Pdf;

        $pdf->addFile( $filePath , 'A');
        $pdf->addFile( $pdfWithBarcode, 'B');

        // Swap out page 19 for the one with the barcode.
        $pdf->cat(1, 18, 'A');
        $pdf->cat(1, null, 'B');
        $pdf->cat(20, 'end', 'A');

        $pdf->flatten()->saveAs( $filePath );


        //------------------------------------------
        // Cleanup

        // Remove tmp barcode file.
        unlink( $barcodeTmpFile );
    }
    
    /**
     * Generate additional pages depending on the LPA's composition.
     */
    protected function generateAdditionalPages()
    {
        Logger::getInstance()->info(
            'Generating Additional Pages',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        $cs1ActorTypes = [];
        
        // CS1 is to be generated when number of attorneys that are larger than what is available on standard form.
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys > 4) {
            $cs1ActorTypes[] = 'primaryAttorney';
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $cs1ActorTypes[] = 'replacementAttorney';
        }
        
        // CS1 is to be generated when number of people to notify are larger than what is available on standard form. 
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > 4) {
            $cs1ActorTypes[] = 'peopleToNotify';
        }
        
        // generate CS1
        if(!empty($cs1ActorTypes)) {
            $generatedCs1 = (new Cs1($this->lpa, $cs1ActorTypes))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        // generate a CS2 page if how attorneys making decisions depends on a special arrangement.
        if($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_ATTORNEY_DECISIONS, $this->lpa->document->primaryAttorneyDecisions->howDetails))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        // generate a CS2 page if how replacement attorneys decisions differs to the default arrangement.
        $content = "";
        if( ((count($this->lpa->document->primaryAttorneys) == 1) 
                || ((count($this->lpa->document->primaryAttorneys) > 1) && ($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)))  
            && (count($this->lpa->document->replacementAttorneys) > 1)) {
                
            switch($this->lpa->document->replacementAttorneyDecisions->how) {
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $content = "Replacement attorneys are to act jointly and severally\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $content = "Replacement attorneys are to act jointly for some decisions and jointly and severally for others, as below:\r\n" . $this->lpa->document->replacementAttorneyDecisions->howDetails . "\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    // default arrangement
                    //$content = "Replacement attorneys are to act jointly\r\n";
                    break;
            }
        }
        elseif((count($this->lpa->document->primaryAttorneys) > 1) && ($this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY)) {
            if(count($this->lpa->document->replacementAttorneys) == 1) {
                
                switch($this->lpa->document->replacementAttorneyDecisions->when) {
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST:
                        // default arrangement, as per how primary attorneys making decision arrangement
                        //$content = "Replacement attorney to step in as soon as one original attorney can no longer act\r\n";
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST:
                        $content = "Replacement attorney to step in only when none of the original attorneys can act\r\n";
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS:
                        $content = "How replacement attorneys will replace the original attorneys:\r\n" . $this->lpa->document->replacementAttorneyDecisions->whenDetails;
                        break;
                }
            }
            elseif(count($this->lpa->document->replacementAttorneys) > 1) { 
                if($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                    $content = "Replacement attorneys to step in only when none of the original attorneys can act\r\n";
                    
                    switch($this->lpa->document->replacementAttorneyDecisions->how) {
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                            $content .= "Replacement attorneys are to act jointly and severally\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                            $content .= "Replacement attorneys are to act joint for some decisions, joint and several for other decisions, as below:\r\n" . $this->lpa->document->replacementAttorneyDecisions->howDetails . "\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                            // default arrangement
                            $content = "";
                            break;
                    }
                }
                elseif($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $content = "How replacement attorneys will replace the original attorneys:\r\n". $this->lpa->document->replacementAttorneyDecisions->whenDetails;
                }
            } // endif
        }
        
        if(!empty($content)) {
            $generatedCs2 = (new Cs2($this->lpa, self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN, $content))->generate();
            $this->mergerIntermediateFilePaths($generatedCs2);
        }
        
        
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
        
        $numOfApplicants = count($this->lpa->document->whoIsRegistering);
        
        // Section 12 - Applicants. If number of applicant is greater than 4, duplicate this page as many as needed in order to fit all applicants in.
        if(is_array($this->lpa->document->whoIsRegistering) && ($numOfApplicants > self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM)) {
            $generatedAdditionalApplicantPages = (new Lp1AdditionalApplicantPage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalApplicantPages);
        }

        // Section 15 - additional applicants signature
        if(is_array($this->lpa->document->whoIsRegistering) && ($numOfApplicants > self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM)) {
            $totalAdditionalApplicants = $numOfApplicants - self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
            $totalAdditionalApplicantPages = ceil($totalAdditionalApplicants/self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);
            if($totalAdditionalApplicantPages > 0) {
                $generatedAdditionalApplicantSignaturePages = (new Lp1AdditionalApplicantSignaturePage($this->lpa))->generate();
                $this->mergerIntermediateFilePaths($generatedAdditionalApplicantSignaturePages);
            }
        }
        
    } // function generateAdditionalPagePdfs()
    
    protected function generateCoversheets()
    {
        Logger::getInstance()->info(
            'Generating Coversheets',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        if( !$this->registrationIsComplete ) {
            $coversheetInstrument = (new CoversheetInstrument($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($coversheetInstrument);
        }
        else {
            $coversheetRegistration = (new CoversheetRegistration($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($coversheetRegistration);
        }
    }
    
    protected function dataMapping()
    {
        /**
         * Donor section (section 1)
         */
        $this->pdfFormData['lpa-id'] = Formatter::id($this->lpa->id);
        $this->pdfFormData['lpa-document-donor-name-title'] = $this->lpa->document->donor->name->title;
        $this->pdfFormData['lpa-document-donor-name-first'] = $this->lpa->document->donor->name->first;
        $this->pdfFormData['lpa-document-donor-name-last'] = $this->lpa->document->donor->name->last;
        $this->pdfFormData['lpa-document-donor-otherNames'] = $this->lpa->document->donor->otherNames;
        $this->pdfFormData['lpa-document-donor-dob-date-day'] =  $this->lpa->document->donor->dob->date->format('d');
        $this->pdfFormData['lpa-document-donor-dob-date-month'] = $this->lpa->document->donor->dob->date->format('m');
        $this->pdfFormData['lpa-document-donor-dob-date-year'] = $this->lpa->document->donor->dob->date->format('Y');
        $this->pdfFormData['lpa-document-donor-address-address1']= $this->lpa->document->donor->address->address1;
        $this->pdfFormData['lpa-document-donor-address-address2']= $this->lpa->document->donor->address->address2;
        $this->pdfFormData['lpa-document-donor-address-address3']= $this->lpa->document->donor->address->address3;
        $this->pdfFormData['lpa-document-donor-address-postcode']= $this->lpa->document->donor->address->postcode;
        $this->pdfFormData['lpa-document-donor-email-address']= ($this->lpa->document->donor->email instanceof EmailAddress)?$this->lpa->document->donor->email->address:null;
        
        /**
         * attorneys section (section 2)
         */
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->drawingTargets[2] = array('primaryAttorney-2', 'primaryAttorney-3');
        }
        elseif($noOfPrimaryAttorneys == 2) {
            $this->drawingTargets[2] = array('primaryAttorney-2', 'primaryAttorney-3');
        }
        elseif($noOfPrimaryAttorneys == 3) {
            $this->drawingTargets[2] = array('primaryAttorney-3');
        }
        
        if($noOfPrimaryAttorneys > 4) {
            $this->pdfFormData['has-more-than-4-attorneys'] = self::CHECK_BOX_ON;
        }
        
        /**
         * attorney decision section (section 3)
         */
        if($noOfPrimaryAttorneys == 1) {
            $this->pdfFormData['how-attorneys-act'] = 'only-one-attorney-appointed';
        }
        elseif( $this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions ) {
            $this->pdfFormData['how-attorneys-act'] = $this->lpa->document->primaryAttorneyDecisions->how;
        }
        
        /**
         * replacement attorneys section (section 4)
         */
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $this->pdfFormData['has-more-than-2-replacement-attorneys'] = self::CHECK_BOX_ON;
        }
        
        // checkbox for replacement decisions are not taking the default arrangement.
        if($noOfPrimaryAttorneys == 1) {
            
            if(($noOfReplacementAttorneys > 1) && 
                (($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) ||
                    ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS))) {
                    
                    $this->pdfFormData['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
            }
        }
        elseif($noOfPrimaryAttorneys > 1) {
            
            switch($this->lpa->document->primaryAttorneyDecisions->how) {
                case PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                     if($noOfReplacementAttorneys == 1) { 
                        if(($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) ||
                            ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS)) {
                            
                            $this->pdfFormData['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        }
                    }
                    elseif($noOfReplacementAttorneys > 1) {
                        if($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                            
                            $this->pdfFormData['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        }
                        elseif($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                            if(($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) ||
                                ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS)) {
                                
                                $this->pdfFormData['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                            }
                        }
                    }
                    
                    break;
                case PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    if($noOfReplacementAttorneys > 1) {
                        if(($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) ||
                            ($this->lpa->document->replacementAttorneyDecisions->how == ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS)) {
                        
                            $this->pdfFormData['change-how-replacement-attorneys-step-in'] = self::CHECK_BOX_ON;
                        }
                    }
                    break;
            }
            
        }
        
        /**
         * People to notify (Section 6)
         */
        $i=0;
        foreach($this->lpa->document->peopleToNotify as $peopleToNotify) {
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-name-title'] = $peopleToNotify->name->title;
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-name-first'] = $peopleToNotify->name->first;
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-name-last'] = $peopleToNotify->name->last;
            
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-address-address1'] = $peopleToNotify->address->address1;
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-address-address2'] = $peopleToNotify->address->address2;
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-address-address3'] = $peopleToNotify->address->address3;
            $this->pdfFormData['lpa-document-peopleToNotify-'.$i.'-address-postcode'] = $peopleToNotify->address->postcode;
            
            if(++$i == self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) break;
        }
        
        $noOfPeopleToNotify = count($this->lpa->document->peopleToNotify);
        if($noOfPeopleToNotify > self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->pdfFormData['has-more-than-4-notified-people'] = self::CHECK_BOX_ON;
        }
        
        if($noOfPeopleToNotify < self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM) {
            $this->drawingTargets[6] = array();
            for($i=self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $noOfPeopleToNotify; $i>0; $i--) {
                $this->drawingTargets[6][] = 'people-to-notify-'. (self::MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM - $i);
            }
        }
        
        /**
         *  Preference and Instructions. (Section 7)
         */
        if(!empty((string)$this->lpa->document->preference)) {
            if(!$this->canFitIntoTextBox($this->lpa->document->preference)) {
                $this->pdfFormData['has-more-preferences'] = self::CHECK_BOX_ON;
            }
            $this->pdfFormData['lpa-document-preference'] = $this->getContentForBox(0, $this->lpa->document->preference, self::CONTENT_TYPE_PREFERENCES);
        }
        else {
            $this->drawingTargets[7] = array('preference');
        }
        
        if(!empty((string)$this->lpa->document->instruction)) {
            if(!$this->canFitIntoTextBox($this->lpa->document->instruction)) {
                $this->pdfFormData['has-more-instructions'] = self::CHECK_BOX_ON;
            }
            $this->pdfFormData['lpa-document-instruction'] = $this->getContentForBox(0, $this->lpa->document->instruction, self::CONTENT_TYPE_INSTRUCTIONS);
        }
        else {
            $this->drawingTargets[7] = isset($this->drawingTargets[7])? array('preference', 'instruction'):array('instruction');
        }
        
        /**
         * Section 9. Donor signature page
         */
        if($this->lpa->document->donor->canSign === false) {
            $this->pdfFormData['see_continuation_sheet_3'] = 'see continuation sheet 3';
        }
        
        /**
         * Populate certificate provider page (Section 10) 
         */
        $this->pdfFormData['lpa-document-certificateProvider-name-title'] = $this->lpa->document->certificateProvider->name->title;
        $this->pdfFormData['lpa-document-certificateProvider-name-first'] = $this->lpa->document->certificateProvider->name->first;
        $this->pdfFormData['lpa-document-certificateProvider-name-last'] = $this->lpa->document->certificateProvider->name->last;
        
        $this->pdfFormData['lpa-document-certificateProvider-address-address1'] = $this->lpa->document->certificateProvider->address->address1;
        $this->pdfFormData['lpa-document-certificateProvider-address-address2'] = $this->lpa->document->certificateProvider->address->address2;
        $this->pdfFormData['lpa-document-certificateProvider-address-address3'] = $this->lpa->document->certificateProvider->address->address3;
        $this->pdfFormData['lpa-document-certificateProvider-address-postcode'] = $this->lpa->document->certificateProvider->address->postcode;
        
        /**
         * Applicant (Section 12)
         */
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->pdfFormData['who-is-applicant'] = 'donor';
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            $this->pdfFormData['who-is-applicant'] = 'attorney';
            $i = 0;
            foreach($this->lpa->document->whoIsRegistering as $attorneyId) {
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);
                if($attorney instanceof TrustCorporation) {
                    $this->pdfFormData['applicant-'.$i.'-name-last']      = $attorney->name;
                }
                else {
                    $this->pdfFormData['applicant-'.$i.'-name-title']     = $attorney->name->title;
                    $this->pdfFormData['applicant-'.$i.'-name-first']     = $attorney->name->first;
                    $this->pdfFormData['applicant-'.$i.'-name-last']      = $attorney->name->last;
                    $this->pdfFormData['applicant-'.$i.'-dob-date-day']   = $attorney->dob->date->format('d');
                    $this->pdfFormData['applicant-'.$i.'-dob-date-month'] = $attorney->dob->date->format('m');
                    $this->pdfFormData['applicant-'.$i.'-dob-date-year']  = $attorney->dob->date->format('Y');
                }
                
                if(++$i == self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) break;
            }
        }
        
        /**
         * Correspondent (Section 13)
         */
        if($this->lpa->document->correspondent instanceof Correspondence) {
            switch($this->lpa->document->correspondent->who) {
                case Correspondence::WHO_DONOR:
                    $this->pdfFormData['who-is-correspondent'] = 'donor';
                    
                    if ($this->lpa->document->correspondent->contactDetailsEnteredManually === true) {
                        $this->pdfFormData['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                        $this->pdfFormData['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                        $this->pdfFormData['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;
                        $this->pdfFormData['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                        $this->pdfFormData['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                        $this->pdfFormData['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                        $this->pdfFormData['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                    } else {
                        $this->drawingTargets[17] = ['correspondent-empty-name-address'];
                    }
                    break;
                case Correspondence::WHO_ATTORNEY:
                    $isAddressCrossedOut = true;
                    
                    $this->pdfFormData['who-is-correspondent'] = 'attorney';
                    if($this->lpa->document->correspondent->name instanceof Name) {
                        $this->pdfFormData['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                        $this->pdfFormData['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                        $this->pdfFormData['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;
                        
                        if ($this->lpa->document->correspondent->contactDetailsEnteredManually === true) {
                            $this->pdfFormData['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                            $this->pdfFormData['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                            $this->pdfFormData['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                            $this->pdfFormData['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                            $isAddressCrossedOut = false;
                        }
                    }
                    
                    if ($isAddressCrossedOut) {
                        $this->drawingTargets[17] = ['correspondent-empty-address'];
                    }
                    
                    $this->pdfFormData['lpa-document-correspondent-company'] = $this->lpa->document->correspondent->company;
                    
                    break;
                case Correspondence::WHO_OTHER:
                    $this->pdfFormData['who-is-correspondent'] = 'other';
                    $this->pdfFormData['lpa-document-correspondent-name-title'] = $this->lpa->document->correspondent->name->title;
                    $this->pdfFormData['lpa-document-correspondent-name-first'] = $this->lpa->document->correspondent->name->first;
                    $this->pdfFormData['lpa-document-correspondent-name-last'] = $this->lpa->document->correspondent->name->last;
                    $this->pdfFormData['lpa-document-correspondent-company'] = $this->lpa->document->correspondent->company;
                    
                    $this->pdfFormData['lpa-document-correspondent-address-address1'] = $this->lpa->document->correspondent->address->address1;
                    $this->pdfFormData['lpa-document-correspondent-address-address2'] = $this->lpa->document->correspondent->address->address2;
                    $this->pdfFormData['lpa-document-correspondent-address-address3'] = $this->lpa->document->correspondent->address->address3;
                    $this->pdfFormData['lpa-document-correspondent-address-postcode'] = $this->lpa->document->correspondent->address->postcode;
                    break;
            }
            
            // correspondence preference
            if($this->lpa->document->correspondent->contactByPost === true) {
                $this->pdfFormData['correspondent-contact-by-post'] = self::CHECK_BOX_ON;
            }
            
            if($this->lpa->document->correspondent->phone instanceof PhoneNumber) {
                $this->pdfFormData['correspondent-contact-by-phone'] = self::CHECK_BOX_ON;
                $this->pdfFormData['lpa-document-correspondent-phone-number'] = str_replace(" ", "", $this->lpa->document->correspondent->phone->number);
            }
            
            if($this->lpa->document->correspondent->email instanceof EmailAddress) {
                $this->pdfFormData['correspondent-contact-by-email'] = self::CHECK_BOX_ON;
                $this->pdfFormData['lpa-document-correspondent-email-address'] = $this->lpa->document->correspondent->email->address;
            }
            
            if($this->lpa->document->correspondent->contactInWelsh === true) {
                $this->pdfFormData['correspondent-contact-in-welsh'] = self::CHECK_BOX_ON;
            }
        }
        
        /**
         *  Payment section (section 14)
         */
        // Fee reduction, repeat application
        if($this->lpa->repeatCaseNumber !== null) {
            $this->pdfFormData['is-repeat-application'] = self::CHECK_BOX_ON;
            $this->pdfFormData['repeat-application-case-number'] = $this->lpa->repeatCaseNumber;
        }
        
        if($this->lpa->payment instanceof Payment) {
            // payment method
            if($this->lpa->payment->method) {
                $this->pdfFormData['pay-by'] = $this->lpa->payment->method;
            }
            
            if($this->lpa->payment->method == Payment::PAYMENT_TYPE_CARD) {
                $this->pdfFormData['lpa-payment-phone-number'] = "NOT REQUIRED.";
            }
            
            // apply to pay reduced fee
            if(($this->lpa->payment->reducedFeeReceivesBenefits && $this->lpa->payment->reducedFeeAwardedDamages) ||
                $this->lpa->payment->reducedFeeLowIncome ||
                $this->lpa->payment->reducedFeeUniversalCredit) {
                
                $this->pdfFormData['apply-for-fee-reduction'] = self::CHECK_BOX_ON;
            }
            
            // Online payment details
            if($this->lpa->payment->reference !== null) {
                $this->pdfFormData['lpa-payment-reference'] = $this->lpa->payment->reference;
                $this->pdfFormData['lpa-payment-amount'] = '£'.sprintf('%.2f', $this->lpa->payment->amount);
                $this->pdfFormData['lpa-payment-date-day'] = $this->lpa->payment->date->format('d');
                $this->pdfFormData['lpa-payment-date-month'] = $this->lpa->payment->date->format('m');
                $this->pdfFormData['lpa-payment-date-year'] = $this->lpa->payment->date->format('Y');
            }
        }
        
        return $this->pdfFormData;
        
    } // function dataMapping()
    
    /**
     * Merge generated intermediate pdf files
     */
    protected function mergePdfs()
    {
        $pdf = PdftkInstance::getInstance();
        $registrationPdf = PdftkInstance::getInstance();

        $fileTag = $lp1FileTag = 'B';
        if(isset($this->interFileStack['LP1']) && isset($this->interFileStack['Coversheet'])) {
            $pdf->addFile($this->interFileStack['Coversheet'], 'A');
            $pdf->addFile($this->interFileStack['LP1'], $lp1FileTag);

            $registrationPdf->addFile($this->interFileStack['Coversheet'], 'A');
            $registrationPdf->addFile($this->interFileStack['LP1'], $lp1FileTag);
        }
        else {
            throw new \UnexpectedValueException('LP1 pdf was not generated before merging pdf intermediate files');
        }

        //-----------------------------------------------------
        // Cover section
        
        // add cover sheet
        $pdf->cat(1, 'end', 'A');


        //-----------------------------------------------------
        // Instrument section
        
        // add page 1-15
        $pdf->cat(1, 15, $lp1FileTag);
        
        // Section 11 - additional attorneys signature
        if(isset($this->interFileStack['AdditionalAttorneySignature'])) {
            foreach($this->interFileStack['AdditionalAttorneySignature'] as $additionalAttorneySignature) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($additionalAttorneySignature, $fileTag);
                
                // add an additional attorney signature page
                $pdf->cat(1, null, $fileTag);
            }
        }

        // Continuation Sheet 1
        if(isset($this->interFileStack['CS1'])) {
            foreach ($this->interFileStack['CS1'] as $cs1) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($cs1, $fileTag);
        
                // add a CS1 page
                $pdf->cat(1, null, $fileTag);
            }
        }
        
        // Continuation Sheet 2
        if(isset($this->interFileStack['CS2'])) {
            foreach ($this->interFileStack['CS2'] as $cs2) {
                $fileTag = $this->nextTag($fileTag);
                $pdf->addFile($cs2, $fileTag);
        
                // add a CS2 page
                $pdf->cat(1, null, $fileTag);
            }
        }
        
        // Continuation Sheet 3
        if(isset($this->interFileStack['CS3'])) {
            $fileTag = $this->nextTag($fileTag);
            $pdf->addFile($this->interFileStack['CS3'], $fileTag);
        
            // add a CS3 page
            $pdf->cat(1, null, $fileTag);
        }
        
        // Continuation Sheet 4
        if(isset($this->interFileStack['CS4'])) {
            $fileTag = $this->nextTag($fileTag);
            $pdf->addFile($this->interFileStack['CS4'], $fileTag);
        
            // add a CS4 page
            $pdf->cat(1, null, $fileTag);
        }


        //-----------------------------------------------------
        // Registration section

        // Use a different instance for the rest of the registration
        // pages so that (if needed) we can apply a stamp to them.

        // Add the registration coversheet.
        $registrationPdf->cat(16, null, $lp1FileTag);

        $registrationPdf->cat(17, null, $lp1FileTag);

        // Section 12 additional applicants
        if(isset($this->interFileStack['AdditionalApplicant'])) {
            foreach($this->interFileStack['AdditionalApplicant'] as $additionalApplicant) {
                $fileTag = $this->nextTag($fileTag);
                $registrationPdf->addFile($additionalApplicant, $fileTag);

                // add an additional applicant page
                $registrationPdf->cat(1, null, $fileTag);
            }
        }

        // add page 18, 19, 20
        $registrationPdf->cat(18, 20, $lp1FileTag);

        // Section 15 - additional applicants signature
        if(isset($this->interFileStack['AdditionalApplicantSignature'])) {
            foreach($this->interFileStack['AdditionalApplicantSignature'] as $additionalApplicantSignature) {
                $fileTag = $this->nextTag($fileTag);
                $registrationPdf->addFile($additionalApplicantSignature, $fileTag);

                // add an additional applicant signature page
                $registrationPdf->cat(1, null, $fileTag);
            }
        }

        //---

        /**
         * If the registration section of the LPA isn't complete, we add the warning stamp.
         */
        if( !$this->registrationIsComplete ){

            $registrationPdf = new \mikehaertl\pdftk\Pdf( $registrationPdf );
            $registrationPdf->stamp( $this->pdfTemplatePath.'/RegistrationWatermark.pdf' );

        }

        //-----------

        // Merge the registration section in...
        $fileTag = $this->nextTag($fileTag);
        $pdf->addFile($registrationPdf, $fileTag);
        $pdf->cat(1, 'end', $fileTag);

        //---

        $pdf->saveAs($this->generatedPdfFilePath);

    } // function mergePdfs()

} // class Lp1
