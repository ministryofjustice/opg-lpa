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
     * @var string
     */
    protected string $templateFileName = 'LP1H.pdf';

    /**
     * PDF file name for the coversheet
     *
     * @var string
     */
    protected string $coversheetFileName = 'LP1H_CoversheetRegistration.pdf';

    /**
     * PDF file name for the draft coversheet
     *
     * @var string
     */
    protected string $coversheetFileNameDraft = 'LP1H_CoversheetInstrument.pdf';

    /**
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     *
     * @return void
     */
    protected function populatePageSix(PrimaryAttorneyDecisions $primaryAttorneyDecisions = null): void
    {
        //  Set when primary attorneys can make decisions
        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $strikeThroughArea = ($primaryAttorneyDecisions->isCanSustainLife() ? 'life-sustain-B' : 'life-sustain-A');
            $this->addStrikeThrough($strikeThroughArea, 6);
        }
    }

    /**
     * @return string
     */
    protected function getAreaReferenceSuffix(): string
    {
        return 'hw';
    }
}
