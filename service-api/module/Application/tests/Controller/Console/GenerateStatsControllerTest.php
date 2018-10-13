<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\GenerateStatsController;
use Application\Model\Service\System\Stats as StatsService;
use Application\Model\Service\System\DynamoCronLock;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;

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

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp()
    {
        $this->cronLock = Mockery::mock(DynamoCronLock::class);

        $this->statsService = Mockery::mock(StatsService::class);

        $this->controller = new GenerateStatsController($this->cronLock, $this->statsService);

        $this->logger = Mockery::mock(Logger::class);
        $this->controller->setLogger($this->logger);
    }

    public function testGenerateActionSuccess()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnTrue();

        $this->statsService->shouldReceive('generate')
            ->andReturnNull();

        $this->logger->shouldReceive('info')
            ->with('This node got the GenerateApiStats cron lock for GenerateApiStats')
            ->once();

        $result = $this->controller->generateAction();

        $this->assertNull($result);
    }

    public function testCleanupActionFailedLocked()
    {
        $this->cronLock->shouldReceive('getLock')
            ->andReturnFalse();

        $this->logger->shouldReceive('info')
            ->with('This node did not get the GenerateApiStats cron lock for GenerateApiStats')
            ->once();

        $result = $this->controller->generateAction();

        $this->assertNull($result);
    }
}
