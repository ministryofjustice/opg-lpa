<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\StatusController;
use Application\ControllerFactory\StatusControllerFactory;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;
use ZfcRbac\Service\AuthorizationService;

class StatusControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var StatusControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new StatusControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with(AuthorizationService::class)
            ->andReturn(Mockery::mock(AuthorizationService::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with(ApplicationsService::class)
            ->andReturn(Mockery::mock(ApplicationsService::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with(ProcessingStatusService::class)
            ->andReturn(Mockery::mock(ProcessingStatusService::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, StatusController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(StatusController::class, $controller);
    }
}
