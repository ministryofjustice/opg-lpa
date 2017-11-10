<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * Class Lp1f
 * @package Opg\Lpa\Pdf
 */
class Lp1f extends AbstractLp1
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LP1F.pdf';

    /**
     * PDF file name for the coversheet
     *
     * @var
     */
    protected $coversheetFileName = 'LP1F_CoversheetRegistration.pdf';

    /**
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     */
    protected function populatePageSix(PrimaryAttorneyDecisions $primaryAttorneyDecisions = null)
    {
        //  Set when primary attorneys can make decisions
        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $whenAttorneysMakeDecisions = ($primaryAttorneyDecisions->when == PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW ? 'when-lpa-registered' : 'when-donor-lost-mental-capacity');
            $this->setData('when-attorneys-may-make-decisions', $whenAttorneysMakeDecisions);
        }
    }
}
