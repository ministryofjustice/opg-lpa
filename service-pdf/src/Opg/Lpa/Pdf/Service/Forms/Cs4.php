<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use mikehaertl\pdftk\Pdf;

class Cs4 extends AbstractForm
{
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS4');

        $this->pdfFormData['cs4-trust-corporation-company-registration-number'] = $this->getTrustCorporation()->number;
        $this->pdfFormData['cs4-footer-right'] = $this->config['footer']['cs4'];

        $this->pdf = new Pdf($this->pdfTemplatePath.'/LPC_Continuation_Sheet_4.pdf');

        $this->pdf->fillForm($this->pdfFormData)
                  ->flatten()
                  ->saveAs($filePath);

        return $this->interFileStack;
    }
}
