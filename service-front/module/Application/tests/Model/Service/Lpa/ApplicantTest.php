<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Application;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ApplicantTest extends AbstractServiceTest
{
    /**
     * @var $applicationService Application|MockInterface
     */
    private $applicationService;

    /**
     * @var $service Applicant
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->applicationService = Mockery::mock(Application::class);

        $this->service = new Applicant($this->authenticationService, []);
        $this->service->setLpaApplicationService($this->applicationService);
    }

    public function testRemoveAttorney() : void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')
            ->withArgs([$lpa, [0 => 111, 2 => 333]])
            ->once();

        $this->service->removeAttorney($lpa, 222);
    }

    public function testRemoveAttorneyNotInList() : void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->shouldNotHaveReceived('setWhoIsRegistering');

        $this->service->removeAttorney($lpa, 444);
    }

    public function testCleanUpAttorneyInList() : void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY]),
                'primaryAttorneys' => [new Human(['id' => 333])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, [333]])->once();

        $this->service->cleanUp($lpa);
    }

    public function testCleanUpAttorneyJointDecisions() : void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY]),
                'primaryAttorneys' => [new Human(['id' => 444])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, [444]])->once();

        $this->service->cleanUp($lpa);
    }

    public function testCleanUpAttorneyNotInList() : void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY]),
                'primaryAttorneys' => [new Human(['id' => 444])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, []])->once();

        $this->service->cleanUp($lpa);
    }
}
