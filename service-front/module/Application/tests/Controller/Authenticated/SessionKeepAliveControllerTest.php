<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\SessionKeepAliveController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\JsonModel;

class SessionKeepAliveControllerTest extends AbstractControllerTest
{
    public function setUp()
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
}
