<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\SessionKeepAliveController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\View\Model\JsonModel;
use Laminas\Http\Response;

final class SessionKeepAliveControllerTest extends AbstractControllerTestCase
{
    public function setUp() : void
    {
        parent::setUp();
    }

    public function testIndexActionValidSession() : void
    {
        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);
        $this->sessionManager->shouldReceive('sessionExists')->andReturn(true)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['refreshed' => true], $result->getVariables());
    }

    public function testIndexActionInvalidSession() : void
    {
        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);
        $this->sessionManager->shouldReceive('sessionExists')->andReturn(false)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['refreshed' => false], $result->getVariables());
    }

    public function testSetExpiryAction() : void
    {
        $expireInSeconds = 500;

        $this->request->shouldReceive('isPost')->andReturn(true)->once();

        $this->request
             ->shouldReceive('getContent')
             ->andReturn("{\"expireInSeconds\": $expireInSeconds}")
             ->once();

        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);
        $this->authenticationService
             ->shouldReceive('setSessionExpiry')
             ->withArgs([$expireInSeconds])
             ->andReturn($expireInSeconds)
             ->once();

        $result = $controller->setExpiryAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['remainingSeconds' => $expireInSeconds], $result->getVariables());
    }

    public function testSetExpiryActionMissingPOSTVariable() : void
    {
        $this->request->shouldReceive('isPost')->andReturn(true)->once();

        $this->request
             ->shouldReceive('getContent')
             ->andReturn("{}")
             ->once();

        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);

        $result = $controller->setExpiryAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result->getStatusCode(), 400);
    }

    public function testSetExpiryActionWithInvalidPostReceives400() : void
    {
        $this->request->shouldReceive('isPost')->andReturn(true)->once();

        $this->request
             ->shouldReceive('getContent')
             ->andReturn('')
             ->once();

        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);
        $result = $controller->setExpiryAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result->getStatusCode(), 400);
    }

    public function testSetExpiryActionWithGETReceives405() : void
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var SessionKeepAliveController $controller */
        $controller = $this->getController(SessionKeepAliveController::class);
        $result = $controller->setExpiryAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($result->getStatusCode(), 405);
    }
}
