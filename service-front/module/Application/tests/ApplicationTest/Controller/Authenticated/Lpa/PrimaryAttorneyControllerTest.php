<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PrimaryAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class PrimaryAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var PrimaryAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new PrimaryAttorneyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AttorneyForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\AttorneyForm')->andReturn($this->form);
    }
}