<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\Application;
use App\Service\Lpa\Metadata;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MetadataTest extends TestCase
{
    private const int IF_MATCH_VERSION = 5;

    private MockObject&Application $applicationService;
    private Metadata $service;

    public function setUp(): void
    {
        $this->applicationService = $this->createMock(Application::class);

        $this->service = new Metadata($this->applicationService, $this->createMock(LoggerInterface::class));
    }

    public function testSetReplacementAttorneysConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['replacement-attorneys-confirmed' => true]], self::IF_MATCH_VERSION);

        $result = $this->service->setReplacementAttorneysConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetCertificateProviderSkipped(): void
    {
        $lpa = new Lpa(['id' => 1]);

        $twice = $this->exactly(2);

        $this->applicationService->expects($twice)
            ->method('updateApplication')
            ->willReturnCallback(
                function (int $id, array $metadata, int $version) use ($twice) {
                    /** @psalm-suppress InternalMethod */
                    switch ($twice->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals([1, ['metadata' => [
                            'certificate-provider-was-skipped' => true,
                            ]], self::IF_MATCH_VERSION], [$id, $metadata, $version]);
                            return false;

                        case 2:
                            $this->assertEquals([1, ['metadata' => [
                            'certificate-provider-was-skipped' => true,
                            'certificate-provider-skipped'     => true,
                            ]], self::IF_MATCH_VERSION + 1], [$id, $metadata, $version]);
                            return false;
                    }
                },
            );

        $result = $this->service->setCertificateProviderSkipped($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 2, $result);
    }

    public function testSetPeopleToNotifyConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['people-to-notify-confirmed' => true]], self::IF_MATCH_VERSION);

        $result = $this->service->setPeopleToNotifyConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetRepeatApplicationConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['repeat-application-confirmed' => true]], self::IF_MATCH_VERSION);

        $result = $this->service->setRepeatApplicationConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetInstructionConfirmed(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['instruction-confirmed' => true]], self::IF_MATCH_VERSION);

        $result = $this->service->setInstructionConfirmed($lpa, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testSetAnalyticsReturnCount(): void
    {
        $lpa = new Lpa(['id' => 1]);
        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['analyticsReturnCount' => 10]], self::IF_MATCH_VERSION);

        $result = $this->service->setAnalyticsReturnCount($lpa, 10, self::IF_MATCH_VERSION);

        $this->assertEquals(self::IF_MATCH_VERSION + 1, $result);
    }

    public function testRemoveMetadata(): void
    {
        $lpa = new Lpa(['id' => 1, 'metadata' => ['test-data' => 'Test Value', 'other-data' => 'Leave this']]);

        $this->applicationService->expects($this->once())
            ->method('updateApplication')
            ->with(1, ['metadata' => ['other-data' => 'Leave this']], self::IF_MATCH_VERSION);

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
