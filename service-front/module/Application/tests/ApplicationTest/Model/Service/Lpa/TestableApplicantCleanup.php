<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\ApplicantCleanup;
use Opg\Lpa\DataModel\Lpa\Lpa;

class TestableApplicantCleanup extends ApplicantCleanup
{
    public $updatedApplicantOverride = null;
    public $invalidOverride = null;

    /**
     * @param Lpa $lpa
     * @return array|string
     */
    public function testGetUpdatedApplicant(Lpa $lpa)
    {
        return parent::getUpdatedApplicant($lpa);
    }

    protected function getUpdatedApplicant(Lpa $lpa)
    {
        return $this->updatedApplicantOverride ?: parent::getUpdatedApplicant($lpa);
    }
}
