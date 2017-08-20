<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReplacementAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class ReplacementAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var ReplacementAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ReplacementAttorneyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\AttorneyForm')->andReturn($this->form);
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