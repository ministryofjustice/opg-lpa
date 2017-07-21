<?php

namespace ApplicationTest\Model\Service\Lpa;


use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;

class ApplicantCleanupTest extends \PHPUnit_Framework_TestCase
{
    public function testWhenApplicantInvalid()
    {
        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid(new Lpa());

        $this->assertFalse($result);
    }

    public function testWhenApplicantValid()
    {
        $lpa = FixturesData::getPfLpa();
        //Donor is always a valid applicant no matter the number of primary attorneys or their decisions
        $lpa->document->whoIsRegistering = 'donor';

        $cleanup = new TestableApplicantCleanup($lpa);

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertTrue($result);
    }
}