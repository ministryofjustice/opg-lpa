<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\VerifyEmailAddressController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

class VerifyEmailAddressControllerTest extends AbstractControllerTestCase
{
    protected function getController(string $controllerName)
    {
        $controller = parent::getController($controllerName);
        $controller->setUserService($this->userDetails);

        return $controller;
    }

    public function testIndexAction(): void
    {
        $controller = $this->getController(VerifyEmailAddressController::class);

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testVerifyActionInvalidToken(): void
    {
        $controller = $this->getController(VerifyEmailAddressController::class);

        $response = new Response();

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->andReturn(true);

        $this->params
            ->shouldReceive('fromRoute')
            ->withArgs(['token'])
            ->andReturn('InvalidToken')
            ->once();

        $this->userDetails
            ->shouldReceive('updateEmailUsingToken')
            ->withArgs(['InvalidToken'])
            ->andReturn(false)
            ->once();

        $this->flashMessenger
            ->shouldReceive('addErrorMessage')
            ->withArgs(['There was an error updating your email address'])
            ->once();

        $this->redirect
            ->shouldReceive('toRoute')
            ->withArgs(['login'])
            ->andReturn($response)
            ->once();

        $result = $controller->verifyAction();

        $this->assertEquals($response, $result);
    }

    public function testVerifyActionValidToken(): void
    {
        $controller = $this->getController(VerifyEmailAddressController::class);

        $response = new Response();

        $this->sessionUtility
            ->shouldReceive('hasInMvc')
            ->andReturn(true);

        $this->params
            ->shouldReceive('fromRoute')
            ->withArgs(['token'])
            ->andReturn('ValidToken')
            ->once();

        $this->userDetails
            ->shouldReceive('updateEmailUsingToken')
            ->withArgs(['ValidToken'])
            ->andReturn(true)
            ->once();

        $this->flashMessenger
            ->shouldReceive('addSuccessMessage')
            ->withArgs(['Your email address was successfully updated. Please login with your new address.'])
            ->once();

        $this->redirect
            ->shouldReceive('toRoute')
            ->withArgs(['login'])
            ->andReturn($response)
            ->once();

        $result = $controller->verifyAction();

        $this->assertEquals($response, $result);
    }
}
