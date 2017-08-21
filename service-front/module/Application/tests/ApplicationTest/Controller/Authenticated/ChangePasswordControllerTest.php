<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\ChangePasswordController;
use Application\Form\User\ChangePassword;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\View\Model\ViewModel;

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
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\ChangePassword')->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userDetailsSession->user = $this->user;
    }

    public function testIndexAction()
    {
        $this->url->shouldReceive('fromRoute')->with('user/change-password')->andReturn('user/change-password')->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'user/change-password')->once();
        $this->authenticationAdapter->shouldReceive('setEmail')->with($this->user->email->address)->once();
        $this->authenticationService->shouldReceive('setAdapter')->with($this->authenticationAdapter)->once();
        $this->form->shouldReceive('setAuthenticationService')->with($this->authenticationService)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('error'));
        $this->assertEquals('Change your password', $result->getVariable('pageTitle'));
    }
}