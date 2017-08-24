<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController;
use Application\Form\Lpa\HowAttorneysMakeDecisionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class HowPrimaryAttorneysMakeDecisionControllerTest extends AbstractControllerTest
{
    /**
     * @var HowPrimaryAttorneysMakeDecisionController
     */
    private $controller;
    /**
     * @var MockInterface|HowAttorneysMakeDecisionForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new HowPrimaryAttorneysMakeDecisionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(HowAttorneysMakeDecisionForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\HowAttorneysMakeDecisionForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with($this->lpa->document->primaryAttorneyDecisions->flatten())->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }
}