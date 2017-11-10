<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class AbstractContinuationSheet
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractContinuationSheet extends AbstractIndividualPdf
{
    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Add a leading blank page - this is done for all continuation sheets
        $this->insertBlankPage('start');

        $this->setData('cs2-donor-full-name', (string) $lpa->document->donor->name);
    }
}
