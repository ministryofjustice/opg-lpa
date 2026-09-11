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
    private const int IF_MATCH_VERSION = 5;

    private Application|MockInterface $applicationService;
    private Metadata $service;

    public function setUp(): void
    {
        $this->applicationService = Mockery::mock(Application::class);

        $this->service = new Metadata($this->applicationService, Mockery::spy(LoggerInterface::class));
    }

    public function testSetReplacementAttorneysConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['replacement-attorneys-confirmed' => true]], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->setReplacementAttorneysConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetCertificateProviderSkipped(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['certificate-provider-was-skipped' => true]], self::IF_MATCH_VERSION)
            ->once();

        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => [
                'certificate-provider-was-skipped' => true,
                'certificate-provider-skipped'     => true,
            ]], self::IF_MATCH_VERSION + 1)
            ->once();

        $result = $this->service->setCertificateProviderSkipped($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 2, $result);
    }

    public function testSetPeopleToNotifyConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['people-to-notify-confirmed' => true]], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->setPeopleToNotifyConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetRepeatApplicationConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['repeat-application-confirmed' => true]], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->setRepeatApplicationConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetInstructionConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['instruction-confirmed' => true]], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->setInstructionConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetAnalyticsReturnCount(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['analyticsReturnCount' => 10]], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->setAnalyticsReturnCount($lpa, 10, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testRemoveMetadata(): void
    {
        $lpa = new Lpa(['id' => 1, 'metadata' => ['test-data' => 'Test Value', 'other-data' => 'Leave this']]);

        $this->applicationService->shouldReceive('updateApplication')
            ->with(1, ['metadata' => ['other-data' => 'Leave this']], self::IF_MATCH_VERSION)
            ->once();

        $result = $this->service->removeMetadata($lpa, 'test-data', self::IF_MATCH_VERSION);

        $this->assertTrue($result);
        $this->assertEquals(['other-data' => 'Leave this'], $lpa->getMetadata());
    }

    public function testRemoveMetadataNotInArray(): void
    {
        $lpa = new Lpa(['id' => 1, 'metadata' => ['test-data' => 'Test Value']]);

        $result = $this->service->removeMetadata($lpa, 'none-existent-data', self::IF_MATCH_VERSION);

        $this->assertFalse($result);
    }
}
