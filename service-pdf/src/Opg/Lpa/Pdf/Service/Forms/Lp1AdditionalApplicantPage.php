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
            $formData = [];

            for ($j = 0; $j < self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $j++) {
                $attorneyId = $this->lpa->document->whoIsRegistering[(1 + $i) * self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM + $j];
                $attorney = $this->lpa->document->getPrimaryAttorneyById($attorneyId);

                if ($attorney instanceof TrustCorporation) {
                    $formData['applicant-' . $j . '-name-last'] = $attorney->name;
                } else {
                    $formData['applicant-' . $j . '-name-title'] = $attorney->name->title;
                    $formData['applicant-' . $j . '-name-first'] = $attorney->name->first;
                    $formData['applicant-' . $j . '-name-last'] = $attorney->name->last;
                    $formData['applicant-' . $j . '-dob-date-day'] = $attorney->dob->date->format('d');
                    $formData['applicant-' . $j . '-dob-date-month'] = $attorney->dob->date->format('m');
                    $formData['applicant-' . $j . '-dob-date-year'] = $attorney->dob->date->format('Y');
                }

                if (++$totalMappedAdditionalApplicants >= $totalAdditionalApplicant) {
                    break;
                }
            }

            $formData['who-is-applicant'] = 'attorney';

            $lpaType = ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'lp1f' : 'lp1h');
            $formData['footer-registration-right-additional'] = $this->config['footer'][$lpaType]['registration'];

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($formData)
                ->flatten()
                ->saveAs($filePath);
        }

        // draw cross lines if there's any blank slot
        if ($totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM) {
            $formTypeSuffix = ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'pf' : 'hw');

            for ($i = self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $totalAdditionalApplicant % self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM; $i >= 1; $i--) {
                $areaReference = 'additional-applicant-' . (self::MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM - $i) . '-' . $formTypeSuffix;
                $this->addStrikeThrough($areaReference);
            }

            $this->drawStrikeThroughs($filePath);
        }

        return $this->interFileStack;
    }
}
