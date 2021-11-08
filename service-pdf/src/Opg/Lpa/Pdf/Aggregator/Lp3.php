<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\Lp3 as Lp3Pdf;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class Lp3
 * @package Opg\Lpa\Pdf\Aggregator
 */
class Lp3 extends AbstractAggregator
{
    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        //  Loop through the people to notify and set up the individual PDFs
        foreach ($lpa->getDocument()->getPeopleToNotify() as $personToNotify) {
            $this->addPdf(new Lp3Pdf($lpa, $personToNotify, $this->pdftkFactory));
        }
    }
}
