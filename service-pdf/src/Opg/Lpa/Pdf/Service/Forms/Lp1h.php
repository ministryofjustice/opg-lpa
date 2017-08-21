<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Lp1h extends AbstractLp1
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LP1H.pdf';

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        // generate a file path with lpa id and timestamp;
        $this->generatedPdfFilePath = $this->getTmpFilePath('PDF-LP1H');
    }

    protected function dataMapping()
    {
        parent::dataMapping();

        // Section 2
        $i = 0;

        foreach ($this->lpa->document->primaryAttorneys as $primaryAttorney) {
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-title'] = $primaryAttorney->name->title;
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-first'] = $primaryAttorney->name->first;
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $primaryAttorney->name->last;

            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-dob-date-day'] = $primaryAttorney->dob->date->format('d');
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-dob-date-month'] = $primaryAttorney->dob->date->format('m');
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-dob-date-year'] = $primaryAttorney->dob->date->format('Y');

            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address1'] = $primaryAttorney->address->address1;
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address2'] = $primaryAttorney->address->address2;
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-address3'] = $primaryAttorney->address->address3;
            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-address-postcode'] = $primaryAttorney->address->postcode;

            $this->dataForForm['lpa-document-primaryAttorneys-' . $i . '-email-address'] = ($primaryAttorney->email instanceof EmailAddress) ? "\n" . $primaryAttorney->email->address : null;

            if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                break;
            }
        }

        if (count($this->lpa->document->primaryAttorneys) == 1) {
            $this->drawingTargets[1] = array('primaryAttorney-1-hw');
        }

        // Section 4
        $i = 0;

        foreach ($this->lpa->document->replacementAttorneys as $replacementAttorney) {
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-name-title'] = $replacementAttorney->name->title;
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-name-first'] = $replacementAttorney->name->first;
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-name-last'] = $replacementAttorney->name->last;

            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-dob-date-day'] = $replacementAttorney->dob->date->format('d');
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-dob-date-month'] = $replacementAttorney->dob->date->format('m');
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-dob-date-year'] = $replacementAttorney->dob->date->format('Y');

            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-address-address1'] = $replacementAttorney->address->address1;
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-address-address2'] = $replacementAttorney->address->address2;
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-address-address3'] = $replacementAttorney->address->address3;
            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-address-postcode'] = $replacementAttorney->address->postcode;

            $this->dataForForm['lpa-document-replacementAttorneys-' . $i . '-email-address'] = ($replacementAttorney->email instanceof EmailAddress ? "\n" . $replacementAttorney->email->address : null);

            if (++$i == self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) {
                break;
            }
        }

        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);

        if ($noOfReplacementAttorneys == 0) {
            $this->drawingTargets[4] = array('replacementAttorney-0-hw', 'replacementAttorney-1-hw');
        } elseif ($noOfReplacementAttorneys == 1) {
            $this->drawingTargets[4] = array('replacementAttorney-1-hw');
        }

        // Life Sustaining (Section 5)
        if ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $this->drawingTargets[5] = ($this->lpa->document->primaryAttorneyDecisions->canSustainLife === true ? ['life-sustain-B'] : ['life-sustain-A']);
        }

        // Attorney/Replacement signature (Section 11)
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $attorneyIndex = 0;

        foreach ($allAttorneys as $attorney) {
            $this->dataForForm['signature-attorney-' . $attorneyIndex . '-name-title'] = $attorney->name->title;
            $this->dataForForm['signature-attorney-' . $attorneyIndex . '-name-first'] = $attorney->name->first;
            $this->dataForForm['signature-attorney-' . $attorneyIndex . '-name-last'] = $attorney->name->last;

            if (++$attorneyIndex == self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) {
                break;
            }
        }

        $numberOfHumanAttorneys = $attorneyIndex;

        switch ($numberOfHumanAttorneys) {
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
        if ($this->lpa->document->whoIsRegistering == 'donor') {
            $this->drawingTargets[16] = array('applicant-0-hw', 'applicant-1-hw', 'applicant-2-hw', 'applicant-3-hw');
        } elseif (is_array($this->lpa->document->whoIsRegistering)) {
            switch (count($this->lpa->document->whoIsRegistering)) {
                case 3:
                    $this->drawingTargets[16] = array('applicant-3-hw');
                    break;
                case 2:
                    $this->drawingTargets[16] = array('applicant-2-hw', 'applicant-3-hw');
                    break;
                case 1:
                    $this->drawingTargets[16] = array('applicant-1-hw', 'applicant-2-hw', 'applicant-3-hw');
                    break;
            }
        }

        $this->dataForForm['footer-instrument-right'] = $this->config['footer']['lp1h']['instrument'];
        $this->dataForForm['footer-registration-right'] = $this->config['footer']['lp1h']['registration'];

        return $this->dataForForm;
    }
}
