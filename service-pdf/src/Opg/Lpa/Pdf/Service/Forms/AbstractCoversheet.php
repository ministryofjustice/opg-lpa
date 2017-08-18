<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use mikehaertl\pdftk\Pdf;

class AbstractCoversheet extends AbstractForm
{
    /**
     * @return array
     */
    protected $coversheetTemplateFiles = [];

    /**
     * @return array
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('Coversheet');

        //  Get the appropriate PDF coversheet template
        $this->pdf = new Pdf($this->pdfTemplatePath . '//' . $this->coversheetTemplateFiles[$this->lpa->document->type]);
        $this->pdf->flatten()
                  ->saveAs($filePath);

        return $this->interFileStack;
    }
}
