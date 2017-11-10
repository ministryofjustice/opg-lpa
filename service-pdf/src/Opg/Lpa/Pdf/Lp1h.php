<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * Class Lp1h
 * @package Opg\Lpa\Pdf
 */
class Lp1h extends AbstractLp1
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LP1H.pdf';

    /**
     * PDF file name for the coversheet
     *
     * @var
     */
    protected $coversheetFileName = 'LP1H_CoversheetRegistration.pdf';

    /**
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     */
    protected function populatePageSix(PrimaryAttorneyDecisions $primaryAttorneyDecisions = null)
    {
        //  Set when primary attorneys can make decisions
        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $strikeThroughArea = ($primaryAttorneyDecisions->canSustainLife ? 'life-sustain-B' : 'life-sustain-A');
            $this->addStrikeThrough($strikeThroughArea, 6);
        }
    }
}
