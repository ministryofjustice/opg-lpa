<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\CompleteViewParamsHelper;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompleteViewParamsHelperTest extends TestCase
{
    private MvcUrlHelper&MockObject $urlHelper;
    private ContinuationSheets&MockObject $continuationSheets;
    private CompleteViewParamsHelper $helper;

    protected function setUp(): void
    {
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->continuationSheets = $this->createMock(ContinuationSheets::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = []) => '/' . $route . '/' . ($params['lpa-id'] ?? '')
        );
        $this->continuationSheets->method('getContinuationNoteKeys')->willReturn([]);

        $this->helper = new CompleteViewParamsHelper(
            $this->urlHelper,
            $this->continuationSheets,
        );
    }

    public function testBuildReturnsExpectedKeys(): void
    {
        $lpa = FixturesData::getPfLpa();

        $result = $this->helper->build($lpa);

        $this->assertArrayHasKey('lp1Url', $result);
        $this->assertArrayHasKey('cloneUrl', $result);
        $this->assertArrayHasKey('dateCheckUrl', $result);
        $this->assertArrayHasKey('continuationNoteKeys', $result);
        $this->assertArrayHasKey('correspondentName', $result);
        $this->assertArrayHasKey('paymentAmount', $result);
        $this->assertArrayHasKey('paymentReferenceNo', $result);
        $this->assertArrayHasKey('hasRemission', $result);
        $this->assertArrayHasKey('isPaymentSkipped', $result);
    }

    public function testBuildGeneratesCorrectUrls(): void
    {
        $lpa = FixturesData::getPfLpa();

        $result = $this->helper->build($lpa);

        $this->assertSame('/lpa/download/' . $lpa->id, $result['lp1Url']);
        $this->assertSame('/user/dashboard/create-lpa/' . $lpa->id, $result['cloneUrl']);
        $this->assertSame('/lpa/date-check/complete/' . $lpa->id, $result['dateCheckUrl']);
    }

    public function testBuildUsesCorrespondentNameWhenLongName(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->correspondent->setName(new LongName(['title' => 'Mr', 'first' => 'John', 'last' => 'Doe']));

        $result = $this->helper->build($lpa);

        $this->assertInstanceOf(LongName::class, $result['correspondentName']);
    }

    public function testBuildUsesCorrespondentCompanyWhenNoLongName(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->correspondent->setName(null);
        $lpa->document->correspondent->setCompany('Acme Corp');

        $result = $this->helper->build($lpa);

        $this->assertSame('Acme Corp', $result['correspondentName']);
    }

    public function testBuildPassesContinuationNoteKeys(): void
    {
        $keys = ['HAS_TRUST_CORP', 'CANT_SIGN'];

        $continuationSheets = $this->createMock(ContinuationSheets::class);
        $continuationSheets->method('getContinuationNoteKeys')->willReturn($keys);

        $helper = new CompleteViewParamsHelper($this->urlHelper, $continuationSheets);
        $result = $helper->build(FixturesData::getPfLpa());

        $this->assertSame($keys, $result['continuationNoteKeys']);
    }

    /**
     * @dataProvider isPaymentSkippedProvider
     */
    public function testIsPaymentSkipped(string $method, bool $universalCredit, bool $receivesBenefits, bool $awardedDamages, bool $expected): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment->method = $method;
        $lpa->payment->reducedFeeUniversalCredit = $universalCredit;
        $lpa->payment->reducedFeeReceivesBenefits = $receivesBenefits;
        $lpa->payment->reducedFeeAwardedDamages = $awardedDamages;

        $result = $this->helper->build($lpa);

        $this->assertSame($expected, $result['isPaymentSkipped']);
    }

    public static function isPaymentSkippedProvider(): array
    {
        return [
            'cheque payment'                       => [Payment::PAYMENT_TYPE_CHEQUE, false, false, false, true],
            'universal credit'                     => [Payment::PAYMENT_TYPE_CARD, true, false, false, true],
            'benefits and awarded damages'         => [Payment::PAYMENT_TYPE_CARD, false, true, true, true],
            'card payment, no reductions'          => [Payment::PAYMENT_TYPE_CARD, false, false, false, false],
            'benefits only, no awarded damages'    => [Payment::PAYMENT_TYPE_CARD, false, true, false, false],
        ];
    }

    public function testLp3UrlAndPeopleToNotifyIncludedWhenPeopleToNotifyPresent(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->peopleToNotify = [new NotifiedPerson()];

        $result = $this->helper->build($lpa);

        $this->assertArrayHasKey('lp3Url', $result);
        $this->assertArrayHasKey('peopleToNotify', $result);
        $this->assertSame('/lpa/download/' . $lpa->id, $result['lp3Url']);
    }

    public function testLp3UrlAbsentWhenNoPeopleToNotify(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->peopleToNotify = [];

        $result = $this->helper->build($lpa);

        $this->assertArrayNotHasKey('lp3Url', $result);
        $this->assertArrayNotHasKey('peopleToNotify', $result);
    }

    public function testLpa120UrlIncludedWhenEligibleForFeeReduction(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment->reducedFeeReceivesBenefits = true;
        $lpa->payment->reducedFeeAwardedDamages = true;
        $lpa->payment->reducedFeeUniversalCredit = false;

        $result = $this->helper->build($lpa);

        $this->assertArrayHasKey('lpa120Url', $result);
        $this->assertTrue($result['hasRemission']);
    }

    public function testLpa120UrlAbsentWhenNotEligibleForFeeReduction(): void
    {
        $lpa = FixturesData::getPfLpa();

        $result = $this->helper->build($lpa);

        $this->assertArrayNotHasKey('lpa120Url', $result);
        $this->assertFalse($result['hasRemission']);
    }
}
