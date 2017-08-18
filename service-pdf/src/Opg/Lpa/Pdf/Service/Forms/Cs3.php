<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Cs3 extends AbstractForm
{
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS3');

        $this->pdfFormData['cs3-donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
        $this->pdfFormData['cs3-footer-right'] = Config::getInstance()['footer']['cs3'];

        $this->pdf = new Pdf($this->pdfTemplatePath."/LPC_Continuation_Sheet_3.pdf");

        $this->pdf->fillForm($this->pdfFormData)
                  ->flatten()
                  ->saveAs($filePath);

        return $this->interFileStack;
    }
}
