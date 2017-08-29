<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class Lp1h extends AbstractLp1
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  'LP1H.pdf';

    /**
     * Get an array of data to use in the LP1 form generation
     *
     * @return array
     */
    protected function getPdfData()
    {
        $formData = parent::getPdfData();

        // Life Sustaining treatment (Section 5)
        $primaryAttorneyDecisions = $this->lpa->document->primaryAttorneyDecisions;

        if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $areaReference = ($primaryAttorneyDecisions->canSustainLife ? 'life-sustain-B' : 'life-sustain-A');
            $this->addStrikeThrough($areaReference, 5);
        }

        return $formData;
    }
}
