<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\PdftkInstance;

class CoversheetInstrument extends AbstractForm
{
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);
    }

    public function generate()
    {
        $this->logger->info('Generating Coversheet Instrument', [
            'lpaId' => $this->lpa->id
        ]);

        $filePath = $this->registerTempFile('Coversheet');

        $coversheetInstrument = PdftkInstance::getInstance($this->pdfTemplatePath . '//' . ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'LP1F_CoversheetInstrument.pdf' : 'LP1H_CoversheetInstrument.pdf'));

        $coversheetInstrument->flatten()
                             ->saveAs($filePath);

        return $this->interFileStack;
    } // function generate()

    public function __destruct()
    {
    }
}