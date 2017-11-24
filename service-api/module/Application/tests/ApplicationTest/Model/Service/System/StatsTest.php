<?php

namespace ApplicationTest\Model\Service\System;

use Application\DataAccess\Mongo\CollectionFactory;
use Application\Model\Service\System\Stats;
use Mockery;
use MongoCollection;
use Opg\Lpa\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceLocatorInterface;

class StatsTest extends TestCase
{
    public function testGenerate()
    {
        $lpaCollection = Mockery::mock(MongoCollection::class);

        //Return 1 for all counts to aid mocking mongo calls
        $lpaCollection->shouldReceive('count')->andReturn(1);

        $manager = Mockery::mock();
        $cursor = Mockery::mock(\Traversable::class);
        $cursor->shouldReceive('toArray')->andReturn([0 => (object)['results' => [(object)['value' => 1], (object)['value' => 1]]]]);
        $manager->shouldReceive('executeCommand')->andReturn($cursor)->once();
        $lpaCollection->shouldReceive('getManager')->andReturn($manager)->once();
        $lpaCollection->shouldReceive('getDatabaseName')->andReturn('test')->once();
        $lpaCollection->shouldReceive('getCollectionName')->andReturn('test')->once();

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('deleteMany')->withArgs([[]])->once();
        $statsLpasCollection->shouldReceive('insertOne')->withArgs(function ($stats) {
            return isset($stats['generated'])
                && isset($stats['lpas'])
                && isset($stats['lpasPerUser'])
                && isset($stats['who'])
                && isset($stats['correspondence'])
                && isset($stats['preferencesInstructions']);
        })->once();

        $statsWhoCollection = Mockery::mock(MongoCollection::class);
        $statsWhoCollection->shouldReceive('count')->andReturn(1);

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with(CollectionFactory::class . '-lpa')->andReturn($lpaCollection);
        $serviceLocatorMock->shouldReceive('get')->with(CollectionFactory::class . '-stats-who')->andReturn($statsWhoCollection);
        $serviceLocatorMock->shouldReceive('get')->with(CollectionFactory::class . '-stats-lpas')->andReturn($statsLpasCollection);
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('err');
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($logger);

        $stats = new Stats();
        $stats->setServiceLocator($serviceLocatorMock);

        $result = $stats->generate();

        $this->assertTrue($result);
    }
}