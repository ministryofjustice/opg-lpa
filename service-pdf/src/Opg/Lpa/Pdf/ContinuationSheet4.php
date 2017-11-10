<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet4
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet4 extends AbstractIndividualPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Add a leading blank page - this is done for all continuation sheets
        $this->insertBlankPage('start');

        //  Get the trust from the attorneys
        $attorneys = array_merge($lpa->document->primaryAttorneys, $lpa->document->replacementAttorneys);

        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $this->setData('cs4-trust-corporation-company-registration-number', $attorney->number);
            }
        }

        //  Set footer data
        $this->setFooter('cs4-footer-right', 'cs4');
    }
}
