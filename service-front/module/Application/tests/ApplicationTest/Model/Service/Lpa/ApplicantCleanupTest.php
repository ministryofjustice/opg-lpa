<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use OpgTest\Lpa\DataModel\FixturesData;

class ApplicantCleanupTest extends \PHPUnit_Framework_TestCase
{
    public function testCleanUpValid()
    {
        $lpa = FixturesData::getPfLpa();
        $lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $lpaApplicationService->shouldNotReceive('deleteWhoIsRegistering');

        $cleanup = new TestableApplicantCleanup();
        $cleanup->invalidOverride = false;

        $cleanup->cleanUp($lpa, $lpaApplicationService);

        $lpaApplicationService->mockery_verify();
        Mockery::close();
    }

    public function testCleanUpInvalid()
    {
        $lpa = FixturesData::getPfLpa();
        $lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $lpaApplicationService->shouldReceive('deleteWhoIsRegistering')->once();

        $cleanup = new TestableApplicantCleanup();
        $cleanup->invalidOverride = true;

        $cleanup->cleanUp($lpa, $lpaApplicationService);

        $lpaApplicationService->mockery_verify();
        Mockery::close();
    }

    public function testWhenApplicantValid()
    {
        $lpa = FixturesData::getPfLpa();
        //Donor is always a valid applicant no matter the number of primary attorneys or their decisions
        $lpa->document->whoIsRegistering = 'donor';

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertFalse($result);
    }

    public function testWhenApplicantValidNullApplicant()
    {
        $lpa = FixturesData::getPfLpa();
        //Null is always a valid applicant no matter the number of primary attorneys or their decisions as user will be forced to select an option
        $lpa->document->whoIsRegistering = null;

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertFalse($result);
    }

    public function testWhenApplicantValidSinglePrimaryAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set applicant as first primary attorney
        $lpa->document->whoIsRegistering = [$lpa->document->primaryAttorneys[0]->id];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertFalse($result);
    }

    public function testWhenApplicantInvalidSinglePrimaryAttorneyMultipleApplicant()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set multiple applicants
        $lpa->document->whoIsRegistering = [1, 2];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertTrue($result);
    }

    public function testWhenApplicantInvalidSinglePrimaryAttorneyIdMismatch()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set single applicant with wrong id
        $lpa->document->whoIsRegistering = [-1];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertTrue($result);
    }

    public function testWhenApplicantInvalid()
    {
        $lpa = FixturesData::getPfLpa();
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($lpa->document->primaryAttorneys));

        //Set applicant as first primary attorney
        $lpa->document->whoIsRegistering = [$lpa->document->primaryAttorneys[0]->id];
        //Set primary attorney decisions as jointly
        $lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertTrue($result);
    }

    public function testWhenApplicantMultiplePrimaryAttorneyIdMismatch()
    {
        $lpa = FixturesData::getPfLpa();
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($lpa->document->primaryAttorneys));
        //Set primary attorney decisions as jointly
        $lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;
        //Make the applicants valid
        $whoIsRegistering = [];
        foreach ($lpa->document->primaryAttorneys as $primaryAttorney) {
            $whoIsRegistering[] = $primaryAttorney->id;
        }
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        //But set one of the applicant ids invalid
        $lpa->document->whoIsRegistering[1] = -1;

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testWhenApplicantInvalid($lpa);

        $this->assertTrue($result);
    }
}