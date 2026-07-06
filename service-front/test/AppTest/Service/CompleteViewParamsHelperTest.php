<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\CompleteViewParamsHelper;
use App\Service\Lpa\ContinuationSheets;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompleteViewParamsHelperTest extends TestCase
{
    private UrlHelper&MockObject $urlHelper;
    private ContinuationSheets&MockObject $continuationSheets;

    protected function setUp(): void
    {
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->continuationSheets = $this->createMock(ContinuationSheets::class);
    }

    public function testBuildIncludesOptionalLinksWhenPeopleToNotifyAndFeeReductionArePresent(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->id = 12345678901;
        $lpa->payment = new Payment([
            'amount' => 41.5,
            'reference' => 'PAY-123',
            'method' => Payment::PAYMENT_TYPE_CARD,
            'reducedFeeUniversalCredit' => true,
        ]);
        $lpa->document->correspondent->name = new LongName([
            'title' => 'Ms',
            'first' => 'Jordan',
            'last' => 'Smith',
        ]);
        $lpa->document->correspondent->company = 'Ignored Company';
        $lpa->document->peopleToNotify = [FixturesData::getNotifiedPerson()];

        $this->continuationSheets->expects($this->once())
            ->method('getContinuationNoteKeys')
            ->with($lpa)
            ->willReturn(['PRIMARY_ATTORNEY_OVERFLOW']);

        $this->urlHelper->expects($this->exactly(5))
            ->method('generate')
            ->willReturnCallback(function (string $route, array $params): string {
                return match ($route . '|' . ($params['pdf-type'] ?? '')) {
                    'lpa/download|lp1' => '/download/lp1',
                    'user/dashboard/create-lpa|' => '/lpa/clone',
                    'lpa/date-check/complete|' => '/lpa/date-check/complete',
                    'lpa/download|lp3' => '/download/lp3',
                    'lpa/download|lpa120' => '/download/lpa120',
                    default => throw new \RuntimeException('Unexpected route'),
                };
            });

        $result = (new CompleteViewParamsHelper($this->urlHelper, $this->continuationSheets))->build($lpa);

        $this->assertSame('/download/lp1', $result['lp1Url']);
        $this->assertSame('/lpa/clone', $result['cloneUrl']);
        $this->assertSame('/lpa/date-check/complete', $result['dateCheckUrl']);
        $this->assertSame('/download/lp3', $result['lp3Url']);
        $this->assertSame('/download/lpa120', $result['lpa120Url']);
        $this->assertSame(['PRIMARY_ATTORNEY_OVERFLOW'], $result['continuationNoteKeys']);
        $this->assertSame($lpa->document->correspondent->name, $result['correspondentName']);
        $this->assertSame(41.5, $result['paymentAmount']);
        $this->assertSame('PAY-123', $result['paymentReferenceNo']);
        $this->assertSame($lpa->document->peopleToNotify, $result['peopleToNotify']);
        $this->assertTrue($result['hasRemission']);
        $this->assertTrue($result['isPaymentSkipped']);
    }

    public function testBuildUsesCompanyNameAndOmitsOptionalLinksWhenNotNeeded(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->id = 42;
        $lpa->payment = new Payment([
            'amount' => 82.0,
            'reference' => 'PAY-999',
            'method' => Payment::PAYMENT_TYPE_CARD,
        ]);
        $lpa->document->correspondent = new Correspondence([
            'company' => 'Example Trust Corp',
        ]);
        $lpa->document->peopleToNotify = [];

        $this->continuationSheets->expects($this->once())
            ->method('getContinuationNoteKeys')
            ->with($lpa)
            ->willReturn([]);

        $this->urlHelper->expects($this->exactly(3))
            ->method('generate')
            ->willReturnMap([
                ['lpa/download', ['lpa-id' => 42, 'pdf-type' => 'lp1'], '/download/lp1'],
                ['user/dashboard/create-lpa', ['lpa-id' => 42], '/lpa/clone'],
                ['lpa/date-check/complete', ['lpa-id' => 42], '/lpa/date-check/complete'],
            ]);

        $result = (new CompleteViewParamsHelper($this->urlHelper, $this->continuationSheets))->build($lpa);

        $this->assertSame('Example Trust Corp', $result['correspondentName']);
        $this->assertFalse($result['hasRemission']);
        $this->assertFalse($result['isPaymentSkipped']);
        $this->assertArrayNotHasKey('lp3Url', $result);
        $this->assertArrayNotHasKey('lpa120Url', $result);
        $this->assertArrayNotHasKey('peopleToNotify', $result);
    }
}
