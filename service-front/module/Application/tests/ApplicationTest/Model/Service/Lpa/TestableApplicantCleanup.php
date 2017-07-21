<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\ApplicantCleanup;
use Opg\Lpa\DataModel\Lpa\Lpa;

class TestableApplicantCleanup extends ApplicantCleanup
{
    /**
     * @param Lpa $lpa
     * @return bool
     */
    public function testWhenApplicantInvalid(Lpa $lpa)
    {
        return parent::whenApplicantInvalid($lpa);
    }
}