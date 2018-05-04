<?php

namespace AuthTest\Model\Service\DataAccess\Mongo\Factory;

use Auth\Model\Service\DataAccess\Mongo\Factory\DatabaseFactory;
use Auth\Model\Service\DataAccess\Mongo\Factory\ManagerFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\ServiceLocatorInterface;

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
            ->withArgs([ManagerFactory::class])->once()
            ->andReturn($manager);

        $this->container->shouldReceive('get')
            ->withArgs(['config'])->once()
            ->andReturn([
                'db' => [
                    'mongo' => [
                        'default' => [
                            'options' => [
                                'db' => 'unit-test'
                            ]
                        ],
                    ],
                ]
            ]);

        $result = $factory->__invoke($this->container, '');

        $this->assertInstanceOf(Database::class, $result);
    }
}
