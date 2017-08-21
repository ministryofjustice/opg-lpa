<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

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

    public function setUp()
    {
        $this->controller = new TypeController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(TypeForm::class);
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
}