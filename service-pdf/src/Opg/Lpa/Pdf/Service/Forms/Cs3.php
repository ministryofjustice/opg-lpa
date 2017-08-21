<?php

namespace Opg\Lpa\Pdf\Service\Forms;

class Cs3 extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LPC_Continuation_Sheet_3.pdf';

    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('CS3');

        $this->dataForForm['cs3-donor-full-name'] = $this->lpa->document->donor->name->__toString();
        $this->dataForForm['cs3-footer-right'] = $this->config['footer']['cs3'];

        $pdf = $this->getPdfObject();
        $pdf->fillForm($this->dataForForm)
            ->flatten()
            ->saveAs($filePath);

        return $this->interFileStack;
    }
}
