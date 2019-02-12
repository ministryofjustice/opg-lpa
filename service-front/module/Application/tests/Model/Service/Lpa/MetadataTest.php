<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Application;
use Application\Model\Service\Lpa\Metadata;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class MetadataTest extends AbstractServiceTest
{
    /**
     * @var $applicationService Application|MockInterface
     */
    private $applicationService;

    /**
     * @var $service Metadata
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->applicationService = Mockery::mock(Application::class);

        $this->service = new Metadata($this->authenticationService, []);
        $this->service->setLpaApplicationService($this->applicationService);
    }

    public function testSetReplacementAttorneysConfirmed() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['replacement-attorneys-confirmed' => true]]])
            ->once();

        $result = $this->service->setReplacementAttorneysConfirmed(new Lpa());

        $this->assertTrue($result);
    }


    public function testSetCertificateProviderSkipped() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['certificate-provider-was-skipped' => true]]])
            ->once();

        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => [
                'certificate-provider-was-skipped' => true,
                'certificate-provider-skipped' => true
                ]]])
            ->once();

        $result = $this->service->setCertificateProviderSkipped(new Lpa());

        $this->assertTrue($result);
    }

    public function testSetPeopleToNotifyConfirmed() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['people-to-notify-confirmed' => true]]])
            ->once();

        $result = $this->service->setPeopleToNotifyConfirmed(new Lpa());

        $this->assertTrue($result);
    }

    public function testSetRepeatApplicationConfirmed() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['repeat-application-confirmed' => true]]])
            ->once();

        $result = $this->service->setRepeatApplicationConfirmed(new Lpa());

        $this->assertTrue($result);
    }

    public function testSetInstructionConfirmed() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['instruction-confirmed' => true]]])
            ->once();

        $result = $this->service->setInstructionConfirmed(new Lpa());

        $this->assertTrue($result);
    }

    public function testSetAnalyticsReturnCount() : void
    {
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['analyticsReturnCount' => 10]]])
            ->once();

        $result = $this->service->setAnalyticsReturnCount(new Lpa(), 10);

        $this->assertTrue($result);
    }

    public function testRemoveMetadata()
    {
        $lpa = new Lpa(['metadata' => ['test-data' => 'Test Value', 'other-data' => 'Leave this']]);

        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([null, ['metadata' => ['other-data' => 'Leave this']]])
            ->once();

        $result = $this->service->removeMetadata($lpa, 'test-data');

        $this->assertTrue($result);
        $this->assertEquals(['other-data' => 'Leave this'], $lpa->getMetadata());
    }

    public function testRemoveMetadataNotInArray()
    {
        $lpa = new Lpa(['metadata' => ['test-data' => 'Test Value']]);

        $result = $this->service->removeMetadata($lpa, 'none-existent-data');

        $this->assertFalse($result);
    }
}
