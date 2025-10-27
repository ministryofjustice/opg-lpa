<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use RuntimeException;
use stdClass;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Model\DataAccess\Postgres\DataFactory;
use Application\Model\DataAccess\Postgres\UserData;

class DataFactoryTest extends MockeryTestCase
{
    /**
     * @var MockInterface|ContainerInterface
     */
    protected $container;

    protected function setUp(): void
    {
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanInstantiate()
    {
        $factory = new DataFactory();
        $this->assertInstanceOf(DataFactory::class, $factory);
    }

    public function testInvalidClass()
    {
        $factory = new DataFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be created with this factory/');

        $factory($this->container, stdClass::class);
    }

    public function testWithValidConfiguration()
    {
        $factory = new DataFactory();

        $zendDbAdapter = Mockery::mock(ZendDbAdapter::class);
        $config = [];

        $this->container->shouldReceive('get')
            ->withArgs(['ZendDbAdapter'])
            ->once()
            ->andReturn($zendDbAdapter);

        $this->container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn($config);

        $result = $factory($this->container, UserData::class);

        $this->assertInstanceOf(UserData::class, $result);
    }
}
