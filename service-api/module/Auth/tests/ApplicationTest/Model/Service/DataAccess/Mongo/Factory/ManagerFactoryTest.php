<?php

namespace ApplicationTest\Model\Service\DataAccess\Mongo\Factory;

use Application\Model\Service\DataAccess\Mongo\Factory\ManagerFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\ServiceLocatorInterface;

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
                        'default' => [
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