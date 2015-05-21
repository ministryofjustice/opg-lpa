<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class Lp1f extends Lp1
{
    use AttorneysTrait;
    
    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);
        
        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP1F');
        
        $this->pdf = PdfProcessor::getPdftkInstance($this->pdfTemplatePath.'/LP1F.pdf');
    }
    
    protected function dataMapping()
    {
        parent::dataMapping();
        
        // Section 2
        $i = 0;
        $primaryAttorneys = $this->sortAttorneys('primaryAttorneys');
        foreach($primaryAttorneys as $primaryAttorney) {
            if($primaryAttorney instanceof TrustCorporation) {
                // $i should always be 0
                $this->pdfFormData['attorney-'.$i.'-is-trust-corporation'] = self::CHECK_BOX_ON;
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = (string)$primaryAttorney->name;
            }
            else {
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-title'] = $primaryAttorney->name->title;
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-first'] = $primaryAttorney->name->first;
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-name-last'] = $primaryAttorney->name->last;
                
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-day'] = $primaryAttorney->dob->date->format('d');
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-month'] = $primaryAttorney->dob->date->format('m');
                $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-dob-date-year'] = $primaryAttorney->dob->date->format('Y');
            }
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address1'] = $primaryAttorney->address->address1;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address2'] = $primaryAttorney->address->address2;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-address3'] = $primaryAttorney->address->address3;
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-address-postcode'] = $primaryAttorney->address->postcode;
            
            $this->pdfFormData['lpa-document-primaryAttorneys-'.$i.'-email-address'] = ($primaryAttorney->email instanceof EmailAddress)?"\n".$primaryAttorney->email->address:null;
            
            if(++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) break;
        }
        
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys == 1) {
            $this->drawingTargets[1] = array('primaryAttorney-1-pf');
        }
        
        // Section 4
        $i = 0;
        $replacementAttorneys = $this->sortAttorneys('replacementAttorneys');
        foreach($replacementAttorneys as $replacementAttorney) {
            if($replacementAttorney instanceof TrustCorporation) {
                $this->pdfFormData['replacement-attorney-'.$i.'-is-trust-corporation']        = self::CHECK_BOX_ON;
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-last']      = (string)$replacementAttorney->name;
            }
            else {
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-title']     = $replacementAttorney->name->title;
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-first']     = $replacementAttorney->name->first;
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-name-last']      = $replacementAttorney->name->last;
                
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-day']   = $replacementAttorney->dob->date->format('d');
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-month'] = $replacementAttorney->dob->date->format('m');
                $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-dob-date-year']  = $replacementAttorney->dob->date->format('Y');
            }
            
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address1'] = $replacementAttorney->address->address1;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address2'] = $replacementAttorney->address->address2;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-address3'] = $replacementAttorney->address->address3;
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-address-postcode'] = $replacementAttorney->address->postcode;
            
            $this->pdfFormData['lpa-document-replacementAttorneys-'.$i.'-email-address']    = ($replacementAttorney->email instanceof EmailAddress)?"\n".$replacementAttorney->email->address:null;
            
            if(++$i == self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) break;
        }
        
        /**
         * When attroney can make decisions (Section 5)
         */
        if ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            if ($this->lpa->document->primaryAttorneyDecisions->when ==
                     PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW) {
                $this->pdfFormData['attorneys-may-make-decisions-when-lpa-registered'] = 'On';
            } elseif ($this->lpa->document->primaryAttorneyDecisions->when ==
                     PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY) {
                $this->pdfFormData['attorneys-may-make-decisions-when-donor-lost-mental-capacity'] = 'On';
            }
        }
        
        // Attorney/Replacement signature (Section 11)
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $attorneyIndex=0;
        foreach($allAttorneys as $attorney) {
            if($attorney instanceof TrustCorporation) continue;
            
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-title'] = $attorney->name->title;
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-first'] = $attorney->name->first;
            $this->pdfFormData['signature-attorney-'.$attorneyIndex.'-name-last'] = $attorney->name->last;
            
            if(++$attorneyIndex == self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) break;
        }
        
        $numberOfHumanAttorneys = $attorneyIndex;
        switch($numberOfHumanAttorneys) {
            case 3:
                $this->drawingTargets[14] = array('attorney-signature-pdf');
                break;
            case 2:
                $this->drawingTargets[13] = array('attorney-signature-pf');
                $this->drawingTargets[14] = array('attorney-signature-pf');
                break;
            case 1:
                $this->drawingTargets[12] = array('attorney-signature-pf');
                $this->drawingTargets[13] = array('attorney-signature-pf');
                $this->drawingTargets[14] = array('attorney-signature-pf');
                break;
        }
        
        return $this->pdfFormData;
    } // function dataMapping();

    protected function generateAdditionalPages ()
    {
        parent::generateAdditionalPages();
        
        // CS1 is generated when number of attorneys that are larger than what is available on standard form. 
        $noOfPrimaryAttorneys = count($this->lpa->document->primaryAttorneys);
        if($noOfPrimaryAttorneys > 4) {
            $generatedCs1 = (new Cs1($this->lpa, 'primaryAttorney'))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
        
        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        if($noOfReplacementAttorneys > 2) {
            $generatedCs1 = (new Cs1($this->lpa, 'replacementAttorney'))->generate();
            $this->mergerIntermediateFilePaths($generatedCs1);
        }
                
        // CS4
        if ($this->hasTrustCorporation()) {
            $generatedCs4 = (new Cs4($this->lpa, $this->getTrustCorporation()->number))->generate();
            $this->mergerIntermediateFilePaths($generatedCs4);
        }
        
        // if number of attorneys (including replacements) is greater than 4, duplicate Section 11 - Attorneys Signatures page  
        // as many as needed to be able to fit all attorneys in the form.
        $totalAttorneys = count($this->lpa->document->primaryAttorneys) + count($this->lpa->document->replacementAttorneys);
        if($this->hasTrustCorporation()) {
            $totalHumanAttorneys = $totalAttorneys - 1;
        }
        else {
            $totalHumanAttorneys = $totalAttorneys;
        }
        
        if( $totalHumanAttorneys > 4 ) {
            $generatedAdditionalAttorneySignaturePages = (new Lp1AdditionalAttorneySignaturePage($this->lpa))->generate();
            $this->mergerIntermediateFilePaths($generatedAdditionalAttorneySignaturePages);
        }
    }
    
    /**
     * get trust corporation object from lpa object or from primary attorneys or replacement attorneys array.
     * 
     * @param string $attorneys
     * @return \Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation|NULL
     */
    protected function getTrustCorporation($attorneys=null)
    {
        if(null == $attorneys) {
            foreach($this->lpa->document->primaryAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
            
            foreach($this->lpa->document->replacementAttorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
        }
        else {
            foreach($attorneys as $attorney) {
                if($attorney instanceof TrustCorporation) {
                    return $attorney;
                }
            }
        }
        
        return null;
    }
} // class