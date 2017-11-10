<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet3
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet3 extends AbstractIndividualPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LPC_Continuation_Sheet_3.pdf';

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Add a leading blank page - this is done for all continuation sheets
        $this->insertBlankPage('start');

        $this->setData('cs3-donor-full-name', (string) $lpa->document->donor->name);

        //  Set footer data
        $this->setFooter('cs3-footer-right', 'cs3');
    }
}
