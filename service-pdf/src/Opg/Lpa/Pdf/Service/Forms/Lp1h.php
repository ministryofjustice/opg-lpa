<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf as PdftkInstance;

class Lp1h extends Lp1
{

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP1H');
        
        $this->pdf = new PdftkInstance($this->pdfTemplatePath.'/LP1H.pdf');
        
    }
    
    protected function dataMapping()
    {
        parent::dataMapping();
        
        // Section 2
        $i = 0;
        foreach($this->lpa->document->primaryAttorneys as $primaryAttorney) {
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-title'] = $primaryAttorney->name->title;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-first'] = $primaryAttorney->name->first;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $primaryAttorney->name->last;
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $primaryAttorney->dob->date->format('d');
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $primaryAttorney->dob->date->format('m');
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $primaryAttorney->dob->date->format('Y');
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address1'] = $primaryAttorney->address->address1;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address2'] = $primaryAttorney->address->address2;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address3'] = $primaryAttorney->address->address3;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-postcode'] = $primaryAttorney->address->postcode;
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-email-address'] = ($primaryAttorney->email instanceof EmailAddress)?"\n".$primaryAttorney->email->address:null;
            
            if(++$i==4) break;
        }
        
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->drawingTargets[1] = array('primaryAttorney-1-hw');
        }
        
        // Section 4
        $i=0;
        foreach($this->lpa->document->replacementAttorneys as $replacementAttorney) {
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-title'] = $replacementAttorney->name->title;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-first'] = $replacementAttorney->name->first;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-last'] = $replacementAttorney->name->last;
            
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-day'] = $replacementAttorney->dob->date->format('d');
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $replacementAttorney->dob->date->format('m');
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-year'] = $replacementAttorney->dob->date->format('Y');
            
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address1'] = $replacementAttorney->address->address1;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address2'] = $replacementAttorney->address->address2;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address3'] = $replacementAttorney->address->address3;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-postcode'] = $replacementAttorney->address->postcode;
            
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-email-address'] = ($replacementAttorney->email instanceof EmailAddress)?"\n".$replacementAttorney->email->address:null;
            
            if(++$i==2) break;
        }

        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys == 0) {
            $this->drawingTargets[4] = array('replacementAttorney-0-hw', 'replacementAttorney-1-hw');
        }
        elseif($noOfReplacementAttorneys == 1) {
            $this->drawingTargets[4] = array('replacementAttorney-1-hw');
        }
        
        // Life Sustaining (Section 5)
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            if($this->lpa->document->primaryAttorneyDecisions->canSustainLife === true) {
                $this->drawingTargets[5] = array('life-sustain-B');
            }
            else {
                $this->drawingTargets[5] = array('life-sustain-A');
            }
        }

        // Attorney/Replacement signature (Section 11)
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $attorneyIndex=0;
        foreach($allAttorneys as $attorney) {
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-title'] = $attorney->name->title;
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-first'] = $attorney->name->first;
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-last'] = $attorney->name->last;
            
            if(++$attorneyIndex == self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) break;
        }
        
        $numberOfHumanAttorneys = $attorneyIndex;
        switch($numberOfHumanAttorneys) {
            case 3:
                $this->drawingTargets[14] = array('attorney-signature-hw');
                break;
            case 2:
                $this->drawingTargets[13] = array('attorney-signature-hw');
                $this->drawingTargets[14] = array('attorney-signature-hw');
                break;
            case 1:
                $this->drawingTargets[12] = array('attorney-signature-hw');
                $this->drawingTargets[13] = array('attorney-signature-hw');
                $this->drawingTargets[14] = array('attorney-signature-hw');
                break;
        }
        

        // Section 12
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->drawingTargets[16] = array('applicant-0-hw','applicant-1-hw','applicant-2-hw','applicant-3-hw');
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            switch(count($this->lpa->document->whoIsRegistering)) {
                case 3:
                    $this->drawingTargets[16] = array('applicant-3-hw');
                    break;
                case 2:
                    $this->drawingTargets[16] = array('applicant-2-hw','applicant-3-hw');
                    break;
                case 1:
                    $this->drawingTargets[16] = array('applicant-1-hw','applicant-2-hw','applicant-3-hw');
                    break;
            }
        }
        
        $this->pdfFormData['footer_instrument_right'] = Config::getInstance()['footer']['lp1h']['instrument'];
        $this->pdfFormData['footer_registration_right'] = Config::getInstance()['footer']['lp1h']['registration'];
        
        return $this->pdfFormData;
    } // function dataMapping()
    
    protected function generateAdditionalPages()
    {
        parent::generateAdditionalPages();
        
        // if number of attorneys (including replacements) is greater than 4, duplicate Section 11 - Attorneys Signatures page  
        // as many as needed to be able to fit all attorneys in the form.
        $totalAttorneys = count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys);
        if( $totalAttorneys > 4 ) {
            $generatedAdditionalAttorneySignaturePages = (new Lp1AdditionalAttorneySignaturePage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneySignaturePages);
        }
    } // function generateAdditionalPages()
} // class