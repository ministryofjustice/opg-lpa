<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Interop\Container\ContainerInterface;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;

use Application\Model\DataAccess\Postgres\DataFactory;
use Application\Model\DataAccess\Postgres\UserData;

class DataFactoryTest extends MockeryTestCase
{
    /**
     * @var MockInterface|ContainerInterface
     */
    protected $container;

    protected function setUp()
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp( '/cannot be created with this factory/' );

        $factory($this->container, \stdClass::class);
    }

    public function testWithValidConfiguration()
    {
        $factory = new DataFactory();

        $zendDbAdapter = Mockery::mock(ZendDbAdapter::class);

        $this->container->shouldReceive('get')
            ->withArgs(['ZendDbAdapter'])
            ->once()
            ->andReturn($zendDbAdapter);

        $result = $factory($this->container, UserData::class);

        $this->assertInstanceOf(UserData::class, $result);
    }
}
