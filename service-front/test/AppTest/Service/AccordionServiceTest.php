<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\AccordionService;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;

final class AccordionServiceTest extends TestCase
{
    private AccordionService $service;

    protected function setUp(): void
    {
        $this->service = new AccordionService();
    }

    public function testGetTopBarsReturnsEmptyArrayWhenLpaMissing(): void
    {
        $this->assertSame([], $this->service->getTopBars(null, 'lpa/form-type'));
    }

    public function testGetBottomBarsReturnsEmptyArrayWhenLpaMissing(): void
    {
        $this->assertSame([], $this->service->getBottomBars(null, 'lpa/form-type'));
    }

    public function testGetTopBarsReturnsAccessibleRoutesAndReviewLink(): void
    {
        $this->assertSame([
            ['routeName' => 'lpa/form-type'],
            ['routeName' => 'lpa/donor'],
            ['routeName' => 'lpa/life-sustaining'],
            ['routeName' => 'lpa/primary-attorney'],
            ['routeName' => 'lpa/how-primary-attorneys-make-decision'],
            ['routeName' => 'lpa/replacement-attorney'],
            ['routeName' => 'lpa/when-replacement-attorney-step-in'],
            ['routeName' => 'lpa/certificate-provider'],
            ['routeName' => 'lpa/people-to-notify'],
            ['routeName' => 'lpa/instructions'],
            ['routeName' => 'lpa/applicant'],
            ['routeName' => 'review-link'],
        ], $this->service->getTopBars($this->makeFixtureLpa(), 'lpa/correspondent'));
    }

    public function testGetBottomBarsReturnsUpcomingAccessibleRoutes(): void
    {
        $this->assertSame([
            ['routeName' => 'lpa/who-are-you'],
            ['routeName' => 'lpa/repeat-application'],
            ['routeName' => 'lpa/fee-reduction'],
        ], $this->service->getBottomBars($this->makeFixtureLpa(), 'lpa/correspondent'));
    }

    public function testGetBottomBarsIncludesFeeReductionWhenPaymentExists(): void
    {
        $this->assertSame([
            ['routeName' => 'lpa/fee-reduction'],
        ], $this->service->getBottomBars($this->makeFixtureLpa(), 'lpa/repeat-application'));
    }

    public function testGetBottomBarsExcludesFeeReductionWhenPaymentMissing(): void
    {
        $lpa = $this->makeFixtureLpa();
        $lpa->payment = null;

        $this->assertSame([], $this->service->getBottomBars($lpa, 'lpa/repeat-application'));
    }

    private function makeFixtureLpa(): Lpa
    {
        return new Lpa((string) file_get_contents(__DIR__ . '/../fixtures/hw.json'));
    }
}
