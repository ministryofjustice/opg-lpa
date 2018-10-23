<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\AccountCleanupController;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

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

    public function setUp()
    {
        $this->cronLock = Mockery::mock(DynamoCronLock::class);

        $this->cleanUpService = Mockery::mock(AccountCleanupService::class);

        $this->controller = new AccountCleanupController($this->cronLock, $this->cleanUpService);
    }

    public function testCleanupActionSuccess()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnTrue();

        $this->cleanUpService->shouldReceive('cleanup')
            ->andReturnNull();

        $result = $this->controller->cleanupAction();

        $this->assertNull($result);
    }

    public function testCleanupActionFailedLocked()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnFalse();

        $result = $this->controller->cleanupAction();

        $this->assertNull($result);
    }
}
