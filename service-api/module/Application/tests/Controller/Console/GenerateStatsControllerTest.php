<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\GenerateStatsController;
use Application\Model\Service\System\Stats as StatsService;
use Application\Model\Service\System\DynamoCronLock;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class GenerateStatsControllerTest extends MockeryTestCase
{
    /**
     * @var GenerateStatsController
     */
    private $controller;

    /**
     * @var DynamoCronLock|MockInterface
     */
    private $cronLock;

    /**
     * @var StatsService|MockInterface
     */
    private $statsService;

    public function setUp()
    {
        $this->cronLock = Mockery::mock(DynamoCronLock::class);

        $this->statsService = Mockery::mock(StatsService::class);

        $this->controller = new GenerateStatsController($this->cronLock, $this->statsService);
    }

    public function testGenerateActionSuccess()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnTrue();

        $this->statsService->shouldReceive('generate')
            ->andReturnNull();

        $result = $this->controller->generateAction();

        $this->assertNull($result);
    }

    public function testCleanupActionFailedLocked()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnFalse();

        $result = $this->controller->generateAction();

        $this->assertNull($result);
    }
}
