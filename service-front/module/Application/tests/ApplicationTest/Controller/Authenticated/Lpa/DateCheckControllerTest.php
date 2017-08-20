<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DateCheckController;
use Application\Form\Lpa\DateCheckForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

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

    public function setUp()
    {
        $this->controller = new DateCheckController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(DateCheckForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\DateCheckForm')->andReturn($this->form);
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