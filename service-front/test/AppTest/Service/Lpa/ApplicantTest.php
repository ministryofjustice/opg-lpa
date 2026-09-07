<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Applicant;
use App\Service\Lpa\Application;
use AppTest\Service\AbstractServiceTest;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mockery;
use Mockery\MockInterface;

final class ApplicantTest extends AbstractServiceTest
{
    private Application|MockInterface $applicationService;
    private Applicant $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->applicationService = Mockery::mock(Application::class);

        $this->service = new Applicant();
        $this->service->setLpaApplicationService($this->applicationService);
    }

    public function testRemoveAttorney(): void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')
            ->withArgs([$lpa, [0 => 111, 2 => 333], 8])
            ->once();

        $newVersion = $this->service->removeAttorney($lpa, 222, 8);
        $this->assertEquals(9, $newVersion);
    }

    public function testRemoveAttorneyNotInList(): void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->shouldNotHaveReceived('setWhoIsRegistering');

        $newVersion = $this->service->removeAttorney($lpa, 444, 7);
        $this->assertEquals(7, $newVersion);
    }

    public function testCleanUpAttorneyInList(): void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY]),
                'primaryAttorneys' => [new Human(['id' => 333])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, [333], 6])->once();

        $newVersion = $this->service->cleanUp($lpa, 6);
        $this->assertEquals(7, $newVersion);
    }

    public function testCleanUpAttorneyJointDecisions(): void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY]),
                'primaryAttorneys' => [new Human(['id' => 444])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, [444], 5])->once();

        $newVersion = $this->service->cleanUp($lpa, 5);
        $this->assertEquals(6, $newVersion);
    }

    public function testCleanUpAttorneyNotInList(): void
    {
        $lpa = new Lpa(['document' => new Document([
                'whoIsRegistering' => [111, 222, 333],
                'primaryAttorneyDecisions' =>
                    new PrimaryAttorneyDecisions(['how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY]),
                'primaryAttorneys' => [new Human(['id' => 444])]
            ])
        ]);

        $this->applicationService->shouldReceive('setWhoIsRegistering')->withArgs([$lpa, [], 4])->once();

        $newVersion = $this->service->cleanUp($lpa, 4);
        $this->assertEquals(5, $newVersion);
    }
}
