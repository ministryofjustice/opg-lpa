<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangeEmailAddressController;
use Application\Form\User\ChangeEmailAddress;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class ChangeEmailAddressControllerTest extends AbstractControllerTest
{
    /**
     * @var ChangeEmailAddressController
     */
    private $controller;
    /**
     * @var MockInterface|ChangeEmailAddress
     */
    private $form;

    public function setUp()
    {
        $this->controller = new ChangeEmailAddressController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(ChangeEmailAddress::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\ChangeEmailAddress')->andReturn($this->form);
    }

    public function testIndexAction()
    {
        $this->controller->indexAction();
    }
}