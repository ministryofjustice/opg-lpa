<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\AccountCleanupController;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;

class AccountCleanupControllerTest extends MockeryTestCase
{
    /**
     * @var AccountCleanupController
     */
    private $controller;

    /**
     * @var DynamoCronLock|MockInterface
     */
    private $cronLock;

    /**
     * @var AccountCleanupService|MockInterface
     */
    private $cleanUpService;

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp()
    {
        $this->cronLock = Mockery::mock(DynamoCronLock::class);

        $this->cleanUpService = Mockery::mock(AccountCleanupService::class);

        $this->controller = new AccountCleanupController($this->cronLock, $this->cleanUpService);

        $this->logger = Mockery::mock(Logger::class);
        $this->controller->setLogger($this->logger);
    }

    public function testCleanupActionSuccess()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnTrue();

        $this->cleanUpService->shouldReceive('cleanup')
            ->andReturnNull();

        $this->logger->shouldReceive('info')
            ->with('This node got the AccountCleanup cron lock for AccountCleanup')
            ->once();

        $result = $this->controller->cleanupAction();

        $this->assertNull($result);
    }

    public function testCleanupActionFailedLocked()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnFalse();

        $this->logger->shouldReceive('info')
            ->with('This node did not get the AccountCleanup cron lock for AccountCleanup')
            ->once();

        $result = $this->controller->cleanupAction();

        $this->assertNull($result);
    }
}
