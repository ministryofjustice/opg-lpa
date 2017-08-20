<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\RegisterController;
use Application\Form\User\Registration;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery\MockInterface;

class RegisterControllerTest extends AbstractControllerTest
{
    /**
     * @var RegisterController
     */
    private $controller;
    /**
     * @var MockInterface|Registration
     */
    private $form;

    public function setUp()
    {
        $this->controller = new RegisterController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(Register::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\General\Registration')->andReturn($this->form);
    }
}