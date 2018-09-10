<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\StatsController;
use Application\ControllerFactory\StatsControllerFactory;
use Application\Model\Service\Stats\Service as StatsService;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class StatsControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var StatsControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new StatsControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with(StatsService::class)
            ->andReturn(Mockery::mock(StatsService::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, StatsController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(StatsController::class, $controller);
    }
}
