<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Exception;
use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ReplacementAttorneyCleanupTest extends AbstractServiceTest
{
    /**
     * @var $lpaApplicationService LpaApplicationService|MockInterface
     */
    private $lpaApplicationService;

    /**
     * @var $service ReplacementAttorneyCleanup
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);

        $this->service = new ReplacementAttorneyCleanup($this->authenticationService, []);
        $this->service->setLpaApplicationService($this->lpaApplicationService);
    }

    /**
     * @throws Exception
     */
    public function testCleanUpWhenDecisionsInvalid() : void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['when' => new DateTime('2018-01-01')]]]);

        $this->lpaApplicationService
            ->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs([Matchers::equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()]
            ])), Matchers::equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }

    public function testCleanUpHowDecisionsInvalid() : void
    {
        $lpa = new Lpa(['document' => ['replacementAttorneyDecisions' => ['how' => 'Test how']]]);

        $this->lpaApplicationService
            ->shouldReceive('setReplacementAttorneyDecisions')
            ->withArgs([Matchers::equalTo(new Lpa([
                'document' => ['replacementAttorneyDecisions' => new ReplacementAttorneyDecisions()]
            ])), Matchers::equalTo(new ReplacementAttorneyDecisions())])
            ->once();

        $this->service->cleanUp($lpa);
    }
}
