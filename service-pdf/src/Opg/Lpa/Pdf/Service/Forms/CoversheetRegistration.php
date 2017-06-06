<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class CoversheetRegistration extends AbstractForm
{
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('Coversheet');

        $coversheetRegistration = PdftkInstance::getInstance($this->pdfTemplatePath . '//' . ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'LP1F_CoversheetRegistration.pdf' : 'LP1H_CoversheetRegistration.pdf'));

        $coversheetRegistration->flatten()
                               ->saveAs($filePath);

        return $this->interFileStack;
    }

    public function __destruct()
    {
    }
}
