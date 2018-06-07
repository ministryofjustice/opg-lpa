<?php

namespace ApplicationTest\Model\DataAccess\Mongo;

use Application\Model\DataAccess\Mongo\CollectionFactory;
use Application\Model\DataAccess\Mongo\DatabaseFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Collection;
use MongoDB\Database;

class CollectionFactoryTest extends MockeryTestCase
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
        $factory = new CollectionFactory('test');

        $database = Mockery::mock(Database::class);

        $this->container->shouldReceive('get')
            ->withArgs([DatabaseFactory::class . '-default'])->once()
            ->andReturn($database);

        $collection = Mockery::mock(Collection::class);

        $database->shouldReceive('selectCollection')
            ->withArgs(['test', ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]])->once()
            ->andReturn($collection);

        $result = $factory->__invoke($this->container, '');

        $this->assertEquals($collection, $result);
    }
}