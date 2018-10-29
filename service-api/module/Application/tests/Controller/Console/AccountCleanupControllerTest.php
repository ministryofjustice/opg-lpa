<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\AccountCleanupController;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
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
     * @var AccountCleanupService|MockInterface
     */
    private $cleanUpService;

    public function setUp()
    {
        $this->cleanUpService = Mockery::mock(AccountCleanupService::class);

        $this->controller = new AccountCleanupController($this->cleanUpService);
    }

    public function testCleanupActionSuccess()
    {
        $this->cleanUpService->shouldReceive('cleanup')
            ->andReturnNull();

        $result = $this->controller->cleanupAction();

        $this->assertNull($result);
    }
}
