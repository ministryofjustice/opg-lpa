<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\AbstractIndividualPdf;
use Opg\Lpa\Pdf\AbstractPdf;

/**
 * Class AbstractAggregator
 * @package Opg\Lpa\Pdf\Aggregator
 */
abstract class AbstractAggregator extends AbstractPdf
{
    /**
     * @var array
     */
    protected $pdfs = [];

    /**
     * Generate the aggregate PDF - this will save a copy to the file system
     *
     * @param bool $protect
     * @return string
     */
    public function generate($protect = false)
    {
        //  Loop through the created PDFs generate them then cat them into the aggregate
        foreach ($this->pdfs as $pdf) {
            $handle = $this->nextHandle();
            $pdfFilePath = $pdf->generate();

            $this->addFile($pdfFilePath, $handle)
                 ->cat(1, 'end', $handle);
        }

        $this->saveAs($this->pdfFile);

        //  Trigger the parent
        return parent::generate($protect);
    }

    /**
     * @param AbstractIndividualPdf $pdf
     */
    protected function addPdf(AbstractIndividualPdf $pdf)
    {
        $this->pdfs[] = $pdf;
    }
}
