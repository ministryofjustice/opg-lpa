<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use DateTime;
use Exception;
use Hamcrest\Matchers;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class ReplacementAttorneyCleanupTest extends MockeryTestCase
{
    private LpaApplicationService|MockInterface $lpaApplicationService;
    private ReplacementAttorneyCleanup $service;

    public function setUp(): void
    {
        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);

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
            ->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs([Matchers::equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()],
            ])), Matchers::equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }

    public function testCleanUpHowDecisionsInvalid(): void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['how' => 'Test how']]]);

        $this->lpaApplicationService
            ->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs([Matchers::equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()],
            ])), Matchers::equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }
}
