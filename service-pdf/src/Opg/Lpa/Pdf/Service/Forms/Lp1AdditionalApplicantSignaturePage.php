<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalApplicantSignaturePage extends AbstractForm
{
    public function generate()
    {
        $this->logGenerationStatement();

        $totalApplicantSignatures = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicantSignatures = $totalApplicantSignatures - Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
        $totalAdditionalApplicantSignaturePages = ceil($totalAdditionalApplicantSignatures/Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);

        for ($i=0; $i<$totalAdditionalApplicantSignaturePages; $i++) {
            $filePath = $this->registerTempFile('AdditionalApplicantSignature');

            $this->pdf = new Pdf($this->pdfTemplatePath. (($this->lpa->document->type == Document::LPA_TYPE_PF)?"/LP1F_AdditionalApplicantSignature.pdf":"/LP1H_AdditionalApplicantSignature.pdf"));

            if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->pdfFormData['footer-registration-right-additional'] = $this->config['footer']['lp1f']['registration'];
            } else {
                $this->pdfFormData['footer-registration-right-additional'] = $this->config['footer']['lp1h']['registration'];
            }

            $this->pdf->fillForm($this->pdfFormData)
                      ->flatten()
                      ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
