<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class Lp1AdditionalApplicantPage extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  [
        Document::LPA_TYPE_PF => 'LP1F_AdditionalApplicant.pdf',
        Document::LPA_TYPE_HW => 'LP1H_AdditionalApplicant.pdf',
    ];

    public function generate()
    {
        $this->logGenerationStatement();

        $totalApplicant = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicant = $totalApplicant - self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM;
        $totalAdditionalPages = ceil($totalAdditionalApplicant / self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM);

        $totalMappedAdditionalApplicants = 0;

        for ($i = 0; $i < $totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('AdditionalApplicant');

            for ($j = 0; $j < self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $j++) {
                $attorneyId = $this->lpa->document->whoIsRegistering[(1 + $i) * self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM + $j];
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);

                if ($attorney instanceof TrustCorporation) {
                    $this->dataForForm['applicant-' . $j . '-name-last'] = $attorney->name;
                } else {
                    $this->dataForForm['applicant-' . $j . '-name-title'] = $attorney->name->title;
                    $this->dataForForm['applicant-' . $j . '-name-first'] = $attorney->name->first;
                    $this->dataForForm['applicant-' . $j . '-name-last'] = $attorney->name->last;
                    $this->dataForForm['applicant-' . $j . '-dob-date-day'] = $attorney->dob->date->format('d');
                    $this->dataForForm['applicant-' . $j . '-dob-date-month'] = $attorney->dob->date->format('m');
                    $this->dataForForm['applicant-' . $j . '-dob-date-year'] = $attorney->dob->date->format('Y');
                }

                if (++$totalMappedAdditionalApplicants >= $totalAdditionalApplicant) {
                    break;
                }
            }

            $this->dataForForm['who-is-applicant'] = 'attorney';

            if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->dataForForm['footer-registration-right-additional'] = $this->config['footer']['lp1f']['registration'];
            } else {
                $this->dataForForm['footer-registration-right-additional'] = $this->config['footer']['lp1h']['registration'];
            }

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($this->dataForForm)
                ->flatten()
                ->saveAs($filePath);
        }

        // draw cross lines if there's any blank slot
        if ($totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
            $crossLineParams = array(array());

            for ($i = self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $i >= 1; $i--) {
                $crossLineParams[0][] = 'additional-applicant-' . (self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $i) . '-' . (($this->lpa->document->type == Document::LPA_TYPE_PF) ? 'pf' : 'hw');
            }

            $this->drawCrossLines($filePath, $crossLineParams);
        }

        return $this->interFileStack;
    }
}
