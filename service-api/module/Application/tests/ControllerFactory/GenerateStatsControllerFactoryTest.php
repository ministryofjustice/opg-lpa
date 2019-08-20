<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\Console\GenerateStatsController;
use Application\ControllerFactory\GenerateStatsControllerFactory;
use Application\Model\Service\System\Stats as StatsService;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class GenerateStatsControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var GenerateStatsControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new GenerateStatsControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with(StatsService::class)
            ->andReturn(Mockery::mock(StatsService::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, GenerateStatsController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(GenerateStatsController::class, $controller);
    }
}
