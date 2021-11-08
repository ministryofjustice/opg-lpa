<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet4
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet4 extends AbstractContinuationSheet
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var string
     */
    protected string $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     *
     * @return void
     */
    protected function create(Lpa $lpa): void
    {
        parent::create($lpa);

        //  Get the trust from the attorneys
        $attorneys = array_merge(
            $lpa->getDocument()->getPrimaryAttorneys(),
            $lpa->getDocument()->getReplacementAttorneys()
        );

        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $this->setData('cs4-trust-corporation-company-registration-number', $attorney->getNumber());
            }
        }

        //  Set footer data
        $this->setFooter('cs4-footer-right', 'cs4');
    }
}
