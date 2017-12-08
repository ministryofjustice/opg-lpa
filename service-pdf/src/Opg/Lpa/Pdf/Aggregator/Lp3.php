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
     */
    protected function create(Lpa $lpa)
    {
        //  Loop through the people to notify and set up the individual PDFs
        foreach ($lpa->document->peopleToNotify as $personToNotify) {
            $this->addPdf(new Lp3Pdf($lpa, $personToNotify));
        }
    }
}
