<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class TypeControllerTest extends AbstractControllerTest
{
    /**
     * @var TypeController
     */
    private $controller;
    /**
     * @var MockInterface|TypeForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new TypeController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(TypeForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\TypeForm')->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with($this->lpa->document->flatten())->once();
        $this->setMatchedRouteName($this->controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->with('lpa/donor', ['lpa-id' => $this->lpa->id])->andReturn('lpa/donor?lpa-id=' .$this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')->with('user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id])->andReturn('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' .$this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' .$this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals('', $result->getVariable('isChangeAllowed'));
        $this->assertEquals([], $result->getVariable('analyticsDimensions'));
    }
}