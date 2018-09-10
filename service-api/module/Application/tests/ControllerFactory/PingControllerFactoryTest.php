<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\PingController;
use Application\ControllerFactory\PingControllerFactory;
use Application\Model\DataAccess\Mongo\DatabaseFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Database;

class PingControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var PingControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new PingControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with('DynamoQueueClient')
            ->andReturn(Mockery::mock(DynamoQueueClient::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with(DatabaseFactory::class . '-default')
            ->andReturn(Mockery::mock(Database::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, PingController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(PingController::class, $controller);
    }
}
