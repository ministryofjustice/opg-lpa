<?php

namespace ApplicationTest\Model\DataAccess\Mongo;

use Application\Model\DataAccess\Mongo\ManagerFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Driver\Manager;

class ManagerFactoryTest extends MockeryTestCase
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
        $factory = new ManagerFactory();

        $this->container->shouldReceive('get')
            ->withArgs(['config'])->once()
            ->andReturn([
                'db' => [
                    'mongo' => [
                        'auth' => [
                            'hosts' => [ 'unittest' ],
                            'options' => [
                                'db' => 'unit-test'
                            ],
                            'driverOptions' => []
                        ],
                    ],
                ]
            ]);

        $result = $factory->__invoke($this->container, '');

        $this->assertInstanceOf(Manager::class, $result);
    }
}