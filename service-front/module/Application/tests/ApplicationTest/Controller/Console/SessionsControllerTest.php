<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\SessionsController;
use Application\Model\Service\System\DynamoCronLock;
use ApplicationTest\Controller\AbstractControllerTest;
use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;
use Mockery;
use Mockery\MockInterface;

class SessionsControllerTest extends AbstractControllerTest
{
    /**
     * @var SessionsController
     */
    private $controller;
    /**
     * @var MockInterface
     */
    private $dynamoCronLock;
    /**
     * @var MockInterface
     */
    private $saveHandler;

    public function setUp()
    {
        $this->controller = new SessionsController();
        parent::controllerSetUp($this->controller);

        $this->dynamoCronLock = Mockery::mock(DynamoCronLock::class);
        $this->serviceLocator->shouldReceive('get')->with('DynamoCronLock')->andReturn($this->dynamoCronLock)->once();
        $this->saveHandler = Mockery::mock(DynamoDbSessionHandler::class);
    }

    public function testGcActionNoLock()
    {
        $this->dynamoCronLock->shouldReceive('getLock')->with('SessionGarbageCollection', ( 60 * 30 ))->andReturn(false)->once();
        $this->sessionManager->shouldReceive('getSaveHandler')->never();
        $this->logger->shouldReceive('info')->with('This node did not get the cron lock for SessionGarbageCollection')->once();

        $result = $this->controller->gcAction();

        $this->assertNull($result);
    }

    public function testGcActionLock()
    {
        $this->dynamoCronLock->shouldReceive('getLock')->with('SessionGarbageCollection', ( 60 * 30 ))->andReturn(true)->once();
        $this->sessionManager->shouldReceive('getSaveHandler')->andReturn($this->saveHandler)->once();
        $this->saveHandler->shouldReceive('garbageCollect')->once();
        $this->logger->shouldReceive('info')->with('This node got the cron lock for SessionGarbageCollection')->once();
        $this->logger->shouldReceive('info')->with('Finished running Session Garbage Collection')->once();

        $result = $this->controller->gcAction();

        $this->assertNull($result);
    }
}