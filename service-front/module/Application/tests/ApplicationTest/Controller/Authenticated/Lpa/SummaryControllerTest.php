<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\ViewModel;

class SummaryControllerTest extends AbstractControllerTest
{
    /**
     * @var SummaryController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(SummaryController::class);
    }

    public function testIndexAction()
    {
        $this->params->shouldReceive('fromQuery')
            ->withArgs(['return-route', 'lpa/applicant'])->andReturn('lpa/applicant')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/applicant', $result->getVariable('returnRoute'));
        $this->assertEquals(82, $result->getVariable('fullFee'));
        $this->assertEquals(41, $result->getVariable('lowIncomeFee'));
    }
}
