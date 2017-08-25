<?php

namespace Opg\Lpa\Pdf\Service\Forms;

class Cs4 extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LPC_Continuation_Sheet_4.pdf';

    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS4');

        $formData = [];
        $formData['cs4-trust-corporation-company-registration-number'] = $this->getTrustCorporation()->number;
        $formData['cs4-footer-right'] = $this->config['footer']['cs4'];

        $pdf = $this->getPdfObject();
        $pdf->fillForm($formData)
            ->flatten()
            ->saveAs($filePath);

        return $this->interFileStack;
    }
}
