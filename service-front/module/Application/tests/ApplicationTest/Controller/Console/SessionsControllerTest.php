<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\SessionsController;
use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\System\DynamoCronLock;
use ApplicationTest\Controller\AbstractControllerTest;
use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;
use Zend\Session\Storage\ArrayStorage;

class SessionsControllerTest extends AbstractControllerTest
{
    /**
     * @var SessionsController
     */
    private $controller;
    /**
     * @var MockInterface|DynamoCronLock
     */
    private $dynamoCronLock;
    /**
     * @var MockInterface|DynamoDbSessionHandler
     */
    private $saveHandler;

    public function setUp()
    {
        $this->storage = new ArrayStorage();

        $this->sessionManager = Mockery::mock(SessionManager::class);
        $this->sessionManager->shouldReceive('getStorage')->andReturn($this->storage);

        $this->logger = Mockery::mock(Logger::class);

        $this->dynamoCronLock = Mockery::mock(DynamoCronLock::class);
        $this->saveHandler = Mockery::mock(DynamoDbSessionHandler::class);

        $this->controller = new SessionsController($this->dynamoCronLock, $this->sessionManager);
        $this->controller->setLogger($this->logger);
    }

    public function testGcActionNoLock()
    {
        $this->dynamoCronLock->shouldReceive('getLock')
            ->withArgs(['SessionGarbageCollection', ( 60 * 30 )])->andReturn(false)->once();
        $this->sessionManager->shouldReceive('getSaveHandler')->never();
        $this->logger->shouldReceive('info')
            ->withArgs(['This node did not get the cron lock for SessionGarbageCollection'])->once();

        $result = $this->controller->gcAction();

        $this->assertNull($result);
    }

    public function testGcActionLock()
    {
        $this->dynamoCronLock->shouldReceive('getLock')
            ->withArgs(['SessionGarbageCollection', ( 60 * 30 )])->andReturn(true)->once();
        $this->sessionManager->shouldReceive('getSaveHandler')->andReturn($this->saveHandler)->once();
        $this->saveHandler->shouldReceive('garbageCollect')->once();
        $this->logger->shouldReceive('info')
            ->withArgs(['This node got the cron lock for SessionGarbageCollection'])->once();
        $this->logger->shouldReceive('info')
            ->withArgs(['Finished running Session Garbage Collection'])->once();

        $result = $this->controller->gcAction();

        $this->assertNull($result);
    }
}
