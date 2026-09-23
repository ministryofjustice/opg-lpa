<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Applicant;
use App\Service\Lpa\Application;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApplicantTest extends TestCase
{
    private MockObject&Application $applicationService;
    private Applicant $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->applicationService = $this->createMock(Application::class);

        $this->service = new Applicant();
        $this->service->setLpaApplicationService($this->applicationService);
    }

    public function testRemoveAttorney(): void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->expects($this->once())
            ->method('setWhoIsRegistering')
            ->with($lpa, [0 => 111, 2 => 333], 8);

        $newVersion = $this->service->removeAttorney($lpa, 222, 8);
        $this->assertEquals(9, $newVersion);
    }

    public function testRemoveAttorneyNotInList(): void
    {
        $lpa = new Lpa(['document' => new Document(['whoIsRegistering' => [111, 222, 333]])]);

        $this->applicationService->expects($this->never())->method('setWhoIsRegistering');

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

        $this->applicationService->expects($this->once())->method('setWhoIsRegistering')->with($lpa, [333], 6);

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

        $this->applicationService->expects($this->once())->method('setWhoIsRegistering')->with($lpa, [444], 5);

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

        $this->applicationService->expects($this->once())->method('setWhoIsRegistering')->with($lpa, [], 4);

        $newVersion = $this->service->cleanUp($lpa, 4);
        $this->assertEquals(5, $newVersion);
    }
}
