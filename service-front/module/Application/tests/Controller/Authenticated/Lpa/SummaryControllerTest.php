<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\View\Model\ViewModel;

class SummaryControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction()
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
        $this->assertEquals(82, $result->getVariable('fullFee'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
    }
}
