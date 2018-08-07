<?php

namespace ApplicationTest\Model\DataAccess\Mongo;

use Application\Model\DataAccess\Mongo\DatabaseFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;

class DatabaseFactoryTest extends MockeryTestCase
{
    /**
     * @var MockInterface|ContainerInterface
     */
    protected $container;

    protected function setUp()
    {
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCreateService()
    {
        $factory = new DatabaseFactory();

        $manager = new Manager('mongodb://unittest');

        $this->container->shouldReceive('get')
            ->withArgs(['config'])->once()
            ->andReturn([
                'db' => [
                    'mongo' => [
                        'auth' => [
                            'hosts' => [
                                'unittest'
                            ],
                            'options' => [
                                'db' => 'unit-test'
                            ],
                            'driverOptions' => []
                        ],
                    ],
                ]
            ]);

        $result = $factory->__invoke($this->container, DatabaseFactory::class . '-auth');

        $this->assertInstanceOf(Database::class, $result);
    }
}
