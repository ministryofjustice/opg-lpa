<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhenLpaStartsController;
use Application\Form\Lpa\WhenLpaStartsForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class WhenLpaStartsControllerTest extends AbstractControllerTest
{
    /**
     * @var WhenLpaStartsController
     */
    private $controller;
    /**
     * @var MockInterface|WhenLpaStartsForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new WhenLpaStartsController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhenLpaStartsForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\WhenLpaStartsForm')->andReturn($this->form);
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