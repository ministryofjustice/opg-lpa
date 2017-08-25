<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class Lp1h extends AbstractLp1
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LP1H.pdf';

    /**
     * Get an array of data to use in the LP1 form generation
     *
     * @return array
     */
    protected function getLp1PdfData()
    {
        $formData = parent::getLp1PdfData();

        // Section 2
        $i = 0;

        foreach ($this->lpa->document->primaryAttorneys as $primaryAttorney) {
            $formData['lpa-document-primaryAttorneys-' . $i . '-name-title'] = $primaryAttorney->name->title;
            $formData['lpa-document-primaryAttorneys-' . $i . '-name-first'] = $primaryAttorney->name->first;
            $formData['lpa-document-primaryAttorneys-' . $i . '-name-last'] = $primaryAttorney->name->last;

            $formData['lpa-document-primaryAttorneys-' . $i . '-dob-date-day'] = $primaryAttorney->dob->date->format('d');
            $formData['lpa-document-primaryAttorneys-' . $i . '-dob-date-month'] = $primaryAttorney->dob->date->format('m');
            $formData['lpa-document-primaryAttorneys-' . $i . '-dob-date-year'] = $primaryAttorney->dob->date->format('Y');

            $formData['lpa-document-primaryAttorneys-' . $i . '-address-address1'] = $primaryAttorney->address->address1;
            $formData['lpa-document-primaryAttorneys-' . $i . '-address-address2'] = $primaryAttorney->address->address2;
            $formData['lpa-document-primaryAttorneys-' . $i . '-address-address3'] = $primaryAttorney->address->address3;
            $formData['lpa-document-primaryAttorneys-' . $i . '-address-postcode'] = $primaryAttorney->address->postcode;

            $formData['lpa-document-primaryAttorneys-' . $i . '-email-address'] = ($primaryAttorney->email instanceof EmailAddress) ? "\n" . $primaryAttorney->email->address : null;

            if (++$i == self::MAX_ATTORNEYS_ON_STANDARD_FORM) {
                break;
            }
        }

        if (count($this->lpa->document->primaryAttorneys) == 1) {
            $this->addStrikeThrough('primaryAttorney-1-hw', 1);
        }

        // Section 4
        $i = 0;

        foreach ($this->lpa->document->replacementAttorneys as $replacementAttorney) {
            $formData['lpa-document-replacementAttorneys-' . $i . '-name-title'] = $replacementAttorney->name->title;
            $formData['lpa-document-replacementAttorneys-' . $i . '-name-first'] = $replacementAttorney->name->first;
            $formData['lpa-document-replacementAttorneys-' . $i . '-name-last'] = $replacementAttorney->name->last;

            $formData['lpa-document-replacementAttorneys-' . $i . '-dob-date-day'] = $replacementAttorney->dob->date->format('d');
            $formData['lpa-document-replacementAttorneys-' . $i . '-dob-date-month'] = $replacementAttorney->dob->date->format('m');
            $formData['lpa-document-replacementAttorneys-' . $i . '-dob-date-year'] = $replacementAttorney->dob->date->format('Y');

            $formData['lpa-document-replacementAttorneys-' . $i . '-address-address1'] = $replacementAttorney->address->address1;
            $formData['lpa-document-replacementAttorneys-' . $i . '-address-address2'] = $replacementAttorney->address->address2;
            $formData['lpa-document-replacementAttorneys-' . $i . '-address-address3'] = $replacementAttorney->address->address3;
            $formData['lpa-document-replacementAttorneys-' . $i . '-address-postcode'] = $replacementAttorney->address->postcode;

            $formData['lpa-document-replacementAttorneys-' . $i . '-email-address'] = ($replacementAttorney->email instanceof EmailAddress ? "\n" . $replacementAttorney->email->address : null);

            if (++$i == self::MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM) {
                break;
            }
        }

        $noOfReplacementAttorneys = count($this->lpa->document->replacementAttorneys);

        if ($noOfReplacementAttorneys == 0) {
            $this->addStrikeThrough('replacementAttorney-0-hw', 4)
                 ->addStrikeThrough('replacementAttorney-1-hw', 4);
        } elseif ($noOfReplacementAttorneys == 1) {
            $this->addStrikeThrough('replacementAttorney-1-hw', 4);
        }

        // Life Sustaining (Section 5)
        if ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $areaReference = ($this->lpa->document->primaryAttorneyDecisions->canSustainLife ? 'life-sustain-B' : 'life-sustain-A');
            $this->addStrikeThrough($areaReference, 5);
        }

        // Attorney/Replacement signature (Section 11)
        $allAttorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        $attorneyIndex = 0;

        foreach ($allAttorneys as $attorney) {
            $formData['signature-attorney-' . $attorneyIndex . '-name-title'] = $attorney->name->title;
            $formData['signature-attorney-' . $attorneyIndex . '-name-first'] = $attorney->name->first;
            $formData['signature-attorney-' . $attorneyIndex . '-name-last'] = $attorney->name->last;

            if (++$attorneyIndex == self::MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM) {
                break;
            }
        }

        $numberOfHumanAttorneys = $attorneyIndex;

        switch ($numberOfHumanAttorneys) {
            case 3:
                $this->addStrikeThrough('attorney-signature-hw', 14);
                break;
            case 2:
                $this->addStrikeThrough('attorney-signature-hw', 13)
                     ->addStrikeThrough('attorney-signature-hw', 14);
                break;
            case 1:
                $this->addStrikeThrough('attorney-signature-hw', 12)
                     ->addStrikeThrough('attorney-signature-hw', 13)
                     ->addStrikeThrough('attorney-signature-hw', 14);
                break;
        }

        // Section 12
        if ($this->lpa->document->whoIsRegistering == 'donor') {
            $this->addStrikeThrough('applicant-0-hw', 16)
                 ->addStrikeThrough('applicant-1-hw', 16)
                 ->addStrikeThrough('applicant-2-hw', 16)
                 ->addStrikeThrough('applicant-3-hw', 16);
        } elseif (is_array($this->lpa->document->whoIsRegistering)) {
            switch (count($this->lpa->document->whoIsRegistering)) {
                case 3:
                    $this->addStrikeThrough('applicant-3-hw', 16);
                    break;
                case 2:
                    $this->addStrikeThrough('applicant-2-hw', 16)
                         ->addStrikeThrough('applicant-3-hw', 16);
                    break;
                case 1:
                    $this->addStrikeThrough('applicant-1-hw', 16)
                         ->addStrikeThrough('applicant-2-hw', 16)
                         ->addStrikeThrough('applicant-3-hw', 16);
                    break;
            }
        }

        return $formData;
    }
}
