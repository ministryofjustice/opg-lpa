<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use DateTimeImmutable;
use Laminas\View\Model\ViewModel;

final class SummaryControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction(): void
    {
        /** @var SummaryController $controller */
        $controller = $this->getController(SummaryController::class);

        $this->params->shouldReceive('fromQuery')
            ->withArgs(['return-route', 'lpa/applicant'])->andReturn('lpa/applicant')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/applicant', $result->getVariable('returnRoute'));
        $feeEffectiveDate = new DateTimeImmutable(getenv('LPA_FEE_EFFECTIVE_DATE') ?: '2025-11-17T00:00:00');
        $timeNow = new DateTimeImmutable('now');
        $fee = ($timeNow >= $feeEffectiveDate) ? 92 : 82;
        $this->assertEquals($fee, $result->getVariable('fullFee'));
        $this->assertEquals($fee / 2, $result->getVariable('lowIncomeFee'));
    }
}
