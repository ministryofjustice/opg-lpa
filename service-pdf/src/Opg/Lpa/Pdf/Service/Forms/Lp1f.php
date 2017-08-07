<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Lp1f extends Lp1
{
    use AttorneysTrait;

    public function __construct (Lpa $lpa)
    {
        parent::__construct($lpa);

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP1F');

        $this->pdf = new Pdf($this->pdfTemplatePath.'/LP1F.pdf');
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
            //pageNo = 1 is page 2
            $pageNo = 1;
            $this->drawingTargets[$pageNo] = array('primaryAttorney-1-pf');
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

        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);
        //pageNo = 4 is page 5
        $pageNo = 4;
        if($noOfReplacementAttorneys == 0) {
            $this->drawingTargets[$pageNo] = array('replacementAttorney-0-pf', 'replacementAttorney-1-pf');
        }
        elseif($noOfReplacementAttorneys == 1) {
            $this->drawingTargets[$pageNo] = array('replacementAttorney-1-pf');
        }

        /**
         * When attroney can make decisions (Section 5)
         */
        if ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            if ($this->lpa->document->primaryAttorneyDecisions->when ==
                     PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW) {
                $this->pdfFormData['when-attorneys-may-make-decisions'] = 'when-lpa-registered';
            } elseif ($this->lpa->document->primaryAttorneyDecisions->when ==
                     PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY) {
                $this->pdfFormData['when-attorneys-may-make-decisions'] = 'when-donor-lost-mental-capacity';
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
                $this->drawingTargets[14] = array('attorney-signature-pf');
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
            case 0:
                $this->drawingTargets[11] = array('attorney-signature-pf');
                $this->drawingTargets[12] = array('attorney-signature-pf');
                $this->drawingTargets[13] = array('attorney-signature-pf');
                $this->drawingTargets[14] = array('attorney-signature-pf');
                break;
        }

        // Section 12
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $this->drawingTargets[16] = array('applicant-0-pf','applicant-1-pf','applicant-2-pf','applicant-3-pf');
        }
        elseif(is_array($this->lpa->document->whoIsRegistering)) {
            switch(count($this->lpa->document->whoIsRegistering)) {
                case 3:
                    $this->drawingTargets[16] = array('applicant-3-pf');
                    break;
                case 2:
                    $this->drawingTargets[16] = array('applicant-2-pf','applicant-3-pf');
                    break;
                case 1:
                    $this->drawingTargets[16] = array('applicant-1-pf','applicant-2-pf','applicant-3-pf');
                    break;
            }
        }

        $this->pdfFormData['footer-instrument-right'] = Config::getInstance()['footer']['lp1f']['instrument'];
        $this->pdfFormData['footer-registration-right'] = Config::getInstance()['footer']['lp1f']['registration'];

        return $this->pdfFormData;
    }

    protected function generateAdditionalPages ()
    {
        parent::generateAdditionalPages();

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
    protected function getTrustCorporation($attorneys = null)
    {
        $trustAttorney = null;

        if ($attorneys == null) {
            $attorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        }

        //  Loop through the attorneys to try to find the trust attorney
        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $trustAttorney = $attorney;
            }
        }

        return $trustAttorney;
    }
} // class
