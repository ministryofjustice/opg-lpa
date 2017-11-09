<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class Lp3Aggregator
 * @package Opg\Lpa\Pdf
 */
class Lp3Aggregator extends AbstractPdf
{
    /**
     * @var array
     */
    private $lp3s = [];

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Loop through the people to notify and set up the individual Lp3 PDFs
        foreach ($lpa->document->peopleToNotify as $personToNotify) {
            $this->lp3s[] = new Lp3($lpa, $personToNotify);
        }
    }

    /**
     * Generate the aggregate PDF - this will save a copy to the file system
     *
     * @param bool $protect
     * @return string
     */
    public function generate($protect = false)
    {
        //  Loop through the created LP3 PDFs, generate them then cat them into the aggregate
        foreach ($this->lp3s as $lp3) {
            $handle = $this->nextHandle();
            $pdfFilePath = $lp3->generate();

            $this->addFile($pdfFilePath, $handle)
                 ->cat(1, 'end', $handle);
        }

        $this->saveAs($this->pdfFile);

        //  Trigger the parent
        return parent::generate($protect);
    }
}
