<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Application;
use App\Service\Lpa\Metadata;
use MakeShared\DataModel\Lpa\Lpa;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

final class MetadataTest extends MockeryTestCase
{
    private Application|MockInterface $applicationService;
    private Metadata $service;

    public function setUp(): void
    {
        $this->applicationService = Mockery::mock(Application::class);

        $this->service = new Metadata();
        $this->service->setLpaApplicationService($this->applicationService);
        $this->service->setLogger(Mockery::spy(LoggerInterface::class));
    }

    public function testSetReplacementAttorneysConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['replacement-attorneys-confirmed' => true]]])
            ->once();

        $result = $this->service->setReplacementAttorneysConfirmed($lpa);

        $this->assertTrue($result);
    }

    public function testSetCertificateProviderSkipped(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['certificate-provider-was-skipped' => true]]])
            ->once();

        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => [
                'certificate-provider-was-skipped' => true,
                'certificate-provider-skipped'     => true,
            ]]])
            ->once();

        $result = $this->service->setCertificateProviderSkipped($lpa);

        $this->assertTrue($result);
    }

    public function testSetPeopleToNotifyConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['people-to-notify-confirmed' => true]]])
            ->once();

        $result = $this->service->setPeopleToNotifyConfirmed($lpa);

        $this->assertTrue($result);
    }

    public function testSetRepeatApplicationConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['repeat-application-confirmed' => true]]])
            ->once();

        $result = $this->service->setRepeatApplicationConfirmed($lpa);

        $this->assertTrue($result);
    }

    public function testSetInstructionConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['instruction-confirmed' => true]]])
            ->once();

        $result = $this->service->setInstructionConfirmed($lpa);

        $this->assertTrue($result);
    }

    public function testSetAnalyticsReturnCount(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['analyticsReturnCount' => 10]]])
            ->once();

        $result = $this->service->setAnalyticsReturnCount($lpa, 10);

        $this->assertTrue($result);
    }

    public function testRemoveMetadata(): void
    {
        $lpa = new Lpa(['id' => 1, 'metadata' => ['test-data' => 'Test Value', 'other-data' => 'Leave this']]);

        $this->applicationService->shouldReceive('updateApplication')
            ->withArgs([1, ['metadata' => ['other-data' => 'Leave this']]])
            ->once();

        $result = $this->service->removeMetadata($lpa, 'test-data');

        $this->assertTrue($result);
        $this->assertEquals(['other-data' => 'Leave this'], $lpa->getMetadata());
    }

    public function testRemoveMetadataNotInArray(): void
    {
        $lpa = new Lpa(['id' => 1, 'metadata' => ['test-data' => 'Test Value']]);

        $result = $this->service->removeMetadata($lpa, 'none-existent-data');

        $this->assertFalse($result);
    }
}
