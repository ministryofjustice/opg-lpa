<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;

class Coversheet extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  [
        Document::LPA_TYPE_PF => 'LP1F_CoversheetInstrument.pdf',
        Document::LPA_TYPE_HW => 'LP1H_CoversheetInstrument.pdf',
    ];

    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        $stateChecker = new StateChecker($lpa);

        //  If the LPA is completed then swap in the proper coversheet
        if ($stateChecker->isStateCompleted()) {
            $this->pdfTemplateFile =  [
                Document::LPA_TYPE_PF => 'LP1F_CoversheetRegistration.pdf',
                Document::LPA_TYPE_HW => 'LP1H_CoversheetRegistration.pdf',
            ];
        }
    }

    /**
     * @return array
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('Coversheet');

        //  Get the appropriate PDF coversheet template
        $pdf = $this->getPdfObject();
        $pdf->flatten()
            ->saveAs($filePath);

        return $this->interFileStack;
    }
}
