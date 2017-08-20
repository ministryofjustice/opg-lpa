<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangePasswordController;
use Application\Form\User\ChangePassword;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class ChangePasswordControllerTest extends AbstractControllerTest
{
    /**
     * @var ChangePasswordController
     */
    private $controller;
    /**
     * @var MockInterface|ChangePassword
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ChangePasswordController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ChangePassword::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\ChangePassword')->andReturn($this->form);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}