<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class Lp1f extends AbstractLp1
{
    /**
     * Get an array of data to use in the LP1 form generation
     *
     * @return array
     */
    protected function getLp1PdfData()
    {
        $formData = parent::getLp1PdfData();

        //  When attorneys can make decisions (Section 5)
        $primaryAttorneyDecisions = $this->lpa->document->primaryAttorneyDecisions;

        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $whenAttorneysCanMakeDecisions = 'when-donor-lost-mental-capacity';

            if ($primaryAttorneyDecisions->when == PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW) {
                $whenAttorneysCanMakeDecisions = 'when-lpa-registered';
            }

            $formData['when-attorneys-may-make-decisions'] = $whenAttorneysCanMakeDecisions;
        }

        return $formData;
    }
}
