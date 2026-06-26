<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Exception;
use Mockery;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mockery\MockInterface;

final class ReplacementAttorneyCleanupTest extends AbstractServiceTest
{
    private LpaApplicationService|MockInterface $lpaApplicationService;
    private ReplacementAttorneyCleanup $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);

        $this->service = new ReplacementAttorneyCleanup($this->authenticationService, []);
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
            ->withArgs([$this->equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()]
            ])), $this->equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }

    public function testCleanUpHowDecisionsInvalid(): void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['how' => 'Test how']]]);

        $this->lpaApplicationService
            ->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs([$this->equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()]
            ])), $this->equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }
}
