<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DateCheckController;
use Application\Form\Lpa\DateCheckForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class DateCheckControllerTest extends AbstractControllerTest
{
    /**
     * @var DateCheckController
     */
    private $controller;
    /**
     * @var MockInterface|DateCheckForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new DateCheckController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(DateCheckForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\DateCheckForm', ['lpa' => $this->lpa])->andReturn($this->form);
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
        $this->params->shouldReceive('fromPost')->with('return-route', null)->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($this->controller, $currentRouteName);
        $this->url->shouldReceive('fromRoute')->with($currentRouteName, ['lpa-id' => $this->lpa->id])->andReturn($currentRouteName)->once();
        $this->form->shouldReceive('setAttribute')->with('action', $currentRouteName)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }
}