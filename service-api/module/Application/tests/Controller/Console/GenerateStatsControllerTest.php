<?php

namespace ApplicationTest\Controller\Console;

use Application\Controller\Console\GenerateStatsController;
use Application\Model\Service\System\Stats as StatsService;
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
     * @var StatsService|MockInterface
     */
    private $statsService;

    public function setUp()
    {
        $this->statsService = Mockery::mock(StatsService::class);

        $this->controller = new GenerateStatsController($this->statsService);
    }

    public function testGenerateActionSuccess()
    {
        $this->statsService->shouldReceive('generate')
            ->andReturnNull();

        $result = $this->controller->generateAction();

        $this->assertNull($result);
    }
}
