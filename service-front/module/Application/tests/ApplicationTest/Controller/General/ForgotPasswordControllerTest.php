<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\ForgotPasswordController;
use Application\Form\User\ResetPasswordEmail;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery\MockInterface;

class ForgotPasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ForgotPasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ResetPasswordEmail
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ForgotPasswordController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ForgotPassword::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\General\ResetPasswordEmail')->andReturn($this->form);
    }
}