<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\Pdf;

class CoversheetRegistration extends AbstractForm
{
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('Coversheet');

        $this->pdf = new Pdf($this->pdfTemplatePath . '//' . ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'LP1F_CoversheetRegistration.pdf' : 'LP1H_CoversheetRegistration.pdf'));

        $this->pdf->flatten()
            ->saveAs($filePath);

        return $this->interFileStack;
    }
}
