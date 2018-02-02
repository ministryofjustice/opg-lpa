<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class ApplicantCleanupTest extends MockeryTestCase
{
    public function testCleanUpValid()
    {
        $lpa = FixturesData::getPfLpa();
        $lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $lpaApplicationService->shouldNotReceive('setWhoIsRegistering');

        $cleanup = new TestableApplicantCleanup();
        $cleanup->updatedApplicantOverride = $lpa->document->whoIsRegistering;

        $result = $cleanup->cleanUp($lpa, $lpaApplicationService);

        $this->assertNull($result);
        $lpaApplicationService->mockery_verify();
        Mockery::close();
    }

    public function testCleanUpInvalid()
    {
        $lpa = FixturesData::getPfLpa();
        $lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $lpaApplicationService->shouldReceive('setWhoIsRegistering')->once();

        $cleanup = new TestableApplicantCleanup();
        $cleanup->updatedApplicantOverride = [1];

        $result = $cleanup->cleanUp($lpa, $lpaApplicationService);

        $this->assertNull($result);
        $lpaApplicationService->mockery_verify();
        Mockery::close();
    }

    public function testWhenApplicantValid()
    {
        $lpa = FixturesData::getPfLpa();
        //Donor is always a valid applicant no matter the number of primary attorneys or their decisions
        $lpa->document->whoIsRegistering = 'donor';

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertEquals($lpa->document->whoIsRegistering, $result);
    }

    public function testWhenApplicantValidNullApplicant()
    {
        $lpa = FixturesData::getPfLpa();
        //Null is always a valid applicant no matter the number of primary attorneys or their decisions as user will be forced to select an option
        $lpa->document->whoIsRegistering = null;

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertEquals($lpa->document->whoIsRegistering, $result);
    }

    public function testWhenApplicantValidSinglePrimaryAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set applicant as first primary attorney
        $lpa->document->whoIsRegistering = [$lpa->document->primaryAttorneys[0]->id];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertEquals($lpa->document->whoIsRegistering, $result);
    }

    public function testWhenApplicantValidMultiplePrimaryAttorneys()
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

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertEquals($lpa->document->whoIsRegistering, $result);
    }

    public function testGetUpdatedApplicantSinglePrimaryAttorneyMultipleApplicant()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set multiple applicants
        $lpa->document->whoIsRegistering = [1, 2];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertNotEquals($lpa->document->whoIsRegistering, $result);
        $this->assertEquals([1], $result);
    }

    public function testGetUpdatedApplicantSinglePrimaryAttorneyIdMismatch()
    {
        $lpa = FixturesData::getHwLpa();
        //Set a single primary attorney
        $lpa->document->primaryAttorneys = [$lpa->document->primaryAttorneys[0]];
        //Set single applicant with wrong id
        $lpa->document->whoIsRegistering = [-1];

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertNotEquals($lpa->document->whoIsRegistering, $result);
        $this->assertEquals([], $result);
    }

    public function testGetUpdatedApplicant()
    {
        $lpa = FixturesData::getPfLpa();
        //Verify there is more than one primary attorney
        $this->assertGreaterThan(1, count($lpa->document->primaryAttorneys));

        //Set applicant as first primary attorney
        $lpa->document->whoIsRegistering = [$lpa->document->primaryAttorneys[0]->id];
        //Set primary attorney decisions as jointly
        $lpa->document->primaryAttorneyDecisions->how = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;

        $cleanup = new TestableApplicantCleanup();

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertNotEquals($lpa->document->whoIsRegistering, $result);
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testGetUpdatedApplicantMultiplePrimaryAttorneyIdMismatch()
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

        $result = $cleanup->testGetUpdatedApplicant($lpa);

        $this->assertNotEquals($lpa->document->whoIsRegistering, $result);
        $this->assertEquals([1, 2, 3], $result);
    }
}
