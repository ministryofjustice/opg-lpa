<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class Lp1AdditionalApplicantSignaturePage extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  [
        Document::LPA_TYPE_PF => 'LP1F_AdditionalApplicantSignature.pdf',
        Document::LPA_TYPE_HW => 'LP1H_AdditionalApplicantSignature.pdf',
    ];

    public function generate()
    {
        $this->logGenerationStatement();

        $totalApplicantSignatures = count($this->lpa->document->whoIsRegistering);
        $totalAdditionalApplicantSignatures = $totalApplicantSignatures - self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM;
        $totalAdditionalApplicantSignaturePages = ceil($totalAdditionalApplicantSignatures/self::MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM);

        for ($i=0; $i<$totalAdditionalApplicantSignaturePages; $i++) {
            $filePath = $this->registerTempFile('AdditionalApplicantSignature');
            $formData = [];

            $lpaType = ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'lp1f' : 'lp1h');
            $formData['footer-registration-right-additional'] = $this->config['footer'][$lpaType]['registration'];

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($formData)
                ->flatten()
                ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
