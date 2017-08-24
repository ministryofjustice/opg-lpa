<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\SummaryController;
use ApplicationTest\Controller\AbstractControllerTest;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class SummaryControllerTest extends AbstractControllerTest
{
    /**
     * @var SummaryController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new SummaryController();
        parent::controllerSetUp($this->controller);

        $this->lpa = FixturesData::getPfLpa();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->params->shouldReceive('fromQuery')->with('return-route', 'lpa/applicant')->andReturn('lpa/applicant')->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with(['whoIsRegistering' => $this->lpa->document->whoIsRegistering])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }
}