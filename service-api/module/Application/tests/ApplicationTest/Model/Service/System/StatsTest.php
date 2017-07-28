<?php

namespace ApplicationTest\Model\Service\System;

use Application\DataAccess\Mongo\ICollectionFactory;
use Application\Model\Service\System\Stats;
use Mockery;
use MongoCollection;
use MongoDB;
use Zend\ServiceManager\ServiceLocatorInterface;

class StatsTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $lpaCollection = Mockery::mock(MongoCollection::class);
        $manager = Mockery::mock();
        $cursor = Mockery::mock(\Traversable::class);
        $cursor->shouldReceive('toArray')->andReturn(['results' => [['value' => 1], ['value' => 1]]]);
        $manager->shouldReceive('executeCommand')->andReturn($cursor)->once();
        $lpaCollection->shouldReceive('getManager')->andReturn($manager)->once();
        $lpaCollection->shouldReceive('getDatabaseName')->andReturn('test')->once();
        $lpaCollection->shouldReceive('getCollectionName')->andReturn('test')->once();

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('remove')->once();
        $statsLpasCollection->shouldReceive('batchInsert')->andReturn(['ok' => true])->once();

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with(ICollectionFactory::class . '-lpa')->andReturn($lpaCollection);
        $serviceLocatorMock->shouldReceive('get')->with(ICollectionFactory::class . '-stats-lpas')->andReturn($statsLpasCollection);

        $stats = new Stats();
        $stats->setServiceLocator($serviceLocatorMock);

        $result = $stats->generate();

        $this->assertTrue($result);
    }
}