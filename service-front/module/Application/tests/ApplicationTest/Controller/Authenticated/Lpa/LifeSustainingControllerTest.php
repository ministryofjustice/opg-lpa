<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\LifeSustainingController;
use Application\Form\Lpa\LifeSustainingForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class LifeSustainingControllerTest extends AbstractControllerTest
{
    /**
     * @var LifeSustainingController
     */
    private $controller;
    /**
     * @var MockInterface|LifeSustainingForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new LifeSustainingController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(LifeSustainingForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\LifeSustainingForm')->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }
}