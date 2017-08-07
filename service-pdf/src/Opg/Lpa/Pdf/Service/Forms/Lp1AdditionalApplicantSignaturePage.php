<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalApplicantSignaturePage extends AbstractForm
{
    /**
     * Duplicate Section 15 for additional applicant signatures
     *
     * @param Lpa $lpa
     */
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logGenerationStatement();

        $totalApplicantSignatures = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicantSignatures = $totalApplicantSignatures - Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
        $totalAdditionalApplicantSignaturePages = ceil($totalAdditionalApplicantSignatures/Lp1::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);

        $totalMappedAdditionalApplicantSignaturePages = 0;
        for($i=0; $i<$totalAdditionalApplicantSignaturePages; $i++) {

            $filePath = $this->registerTempFile('AdditionalApplicantSignature');

            $this->pdf = new Pdf($this->pdfTemplatePath. (($this->lpa->document->type == Document::LPA_TYPE_PF)?"/LP1F_AdditionalApplicantSignature.pdf":"/LP1H_AdditionalApplicantSignature.pdf"));

            if($this->lpa->document->type == Document::LPA_TYPE_PF) {
                $this->pdfFormData['footer-registration-right-additional'] = Config::getInstance()['footer']['lp1f']['registration'];
            }
            else {
                $this->pdfFormData['footer-registration-right-additional'] = Config::getInstance()['footer']['lp1h']['registration'];
            }

            $this->pdf->fillForm($this->pdfFormData)
                      ->flatten()
                      ->saveAs($filePath);
        } // endfor

        return $this->interFileStack;
    }

    public function __destruct()
    {
    }
}