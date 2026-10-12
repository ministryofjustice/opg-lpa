<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use DateTime;
use Exception;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ReplacementAttorneyCleanupTest extends TestCase
{
    private MockObject&LpaApplicationService $lpaApplicationService;
    private ReplacementAttorneyCleanup $service;

    public function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->service = new ReplacementAttorneyCleanup();
        $this->service->setLpaApplicationService($this->lpaApplicationService);
    }

    /**
     * @throws Exception
     */
    public function testCleanUpWhenDecisionsInvalid(): void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['when' => new DateTime('2018-01-01')]]]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->with(
                new Lpa([
                    'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()],
                ]),
                new ReplacementAttorneyDecisions(),
                5,
            );

        $newVersion = $this->service->cleanUp($lpa, 5);
        $this->assertEquals(6, $newVersion);
    }

    public function testCleanUpHowDecisionsInvalid(): void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['how' => 'Test how']]]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setReplacementAttorneyDecisions')
            ->with(
                new Lpa([
                    'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()],
                ]),
                new ReplacementAttorneyDecisions(),
                6,
            );

        $newVersion = $this->service->cleanUp($lpa, 6);
        $this->assertEquals(7, $newVersion);
    }
}
