<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\VerifyEmailAddressController;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class VerifyEmailAddressControllerTest extends AbstractControllerTest
{
    /**
     * @var VerifyEmailAddressController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(VerifyEmailAddressController::class);
        $this->controller->setUserService($this->userDetails);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testVerifyActionInvalidToken()
    {
        $response = new Response();

        $this->sessionManager->shouldReceive('initialise')->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('InvalidToken')->once();
        $this->userDetails->shouldReceive('updateEmailUsingToken')
            ->withArgs(['InvalidToken'])->andReturn(false)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')
            ->withArgs(['There was an error updating your email address'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['login'])->andReturn($response)->once();

        $result = $this->controller->verifyAction();

        $this->assertEquals($response, $result);
    }

    public function testVerifyActionValidToken()
    {
        $response = new Response();

        $this->sessionManager->shouldReceive('initialise')->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('ValidToken')->once();
        $this->userDetails->shouldReceive('updateEmailUsingToken')
            ->withArgs(['ValidToken'])->andReturn(true)->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')
            ->withArgs(['Your email address was successfully updated. Please login with your new address.'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['login'])->andReturn($response)->once();

        $result = $this->controller->verifyAction();

        $this->assertEquals($response, $result);
    }
}
