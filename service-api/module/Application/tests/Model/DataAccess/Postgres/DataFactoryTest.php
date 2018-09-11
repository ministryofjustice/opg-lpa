<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Interop\Container\ContainerInterface;

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

    public function testMissingPostgresConfig()
    {
        $factory = new DataFactory();

        $this->container->shouldReceive('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp( '/Missing Postgres configuration/' );

        $factory($this->container, UserData::class);
    }


    public function testWithValidConfiguration()
    {
        $factory = new DataFactory();

        $this->container->shouldReceive('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn([
                'db' => [
                    'postgres' => [
                        'default' => [
                            'adapter'   => 'pgsql',
                            'host'      => 'test-host',
                            'port'      => 'test-port',
                            'dbname'    => 'test-dbname',
                            'username'  => 'test-username',
                            'password'  => 'test-password',
                        ],
                    ],
                ],
            ]);

        $result = $factory($this->container, UserData::class);

        $this->assertInstanceOf(UserData::class, $result);
    }
}
