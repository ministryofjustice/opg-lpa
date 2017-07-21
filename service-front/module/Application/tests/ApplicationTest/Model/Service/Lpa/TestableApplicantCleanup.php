<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\ApplicantCleanup;
use Opg\Lpa\DataModel\Lpa\Lpa;

class TestableApplicantCleanup extends ApplicantCleanup
{
    public $invalidOverride = null;

    /**
     * @param Lpa $lpa
     * @return bool
     */
    public function testWhenApplicantInvalid(Lpa $lpa)
    {
        return parent::whenApplicantInvalid($lpa);
    }

    protected function whenApplicantInvalid(Lpa $lpa)
    {
        return $this->invalidOverride ?: parent::whenApplicantInvalid($lpa);
    }
}