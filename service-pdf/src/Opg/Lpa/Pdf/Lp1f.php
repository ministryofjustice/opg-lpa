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
     * @var string
     */
    protected string $templateFileName = 'LP1F.pdf';

    /**
     * PDF file name for the coversheet
     *
     * @var string
     */
    protected string $coversheetFileName = 'LP1F_CoversheetRegistration.pdf';

    /**
     * PDF file name for the draft coversheet
     *
     * @var string
     */
    protected string $coversheetFileNameDraft = 'LP1F_CoversheetInstrument.pdf';

    /**
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     *
     * @return void
     */
    protected function populatePageSix(PrimaryAttorneyDecisions $primaryAttorneyDecisions = null): void
    {
        //  Set when primary attorneys can make decisions
        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $whenAttorneysMakeDecisions = (
                $primaryAttorneyDecisions->getWhen() == PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW ?
                    'when-lpa-registered' :
                    'when-donor-lost-mental-capacity'
            );

            $this->setData('when-attorneys-may-make-decisions', $whenAttorneysMakeDecisions);
        }
    }

    /**
     * @return string
     */
    protected function getAreaReferenceSuffix(): string
    {
        return 'pf';
    }
}
