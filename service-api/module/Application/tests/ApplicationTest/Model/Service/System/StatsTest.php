<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\Stats;
use Mockery;
use MongoCollection;
use MongoDB;
use Zend\ServiceManager\ServiceLocatorInterface;

class StatsTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $lpaDb = Mockery::mock(MongoDB::class);
        $lpaDb->shouldReceive('setReadPreference')->once();
        $lpaDb->shouldReceive('command')->andReturn(['results' => [['value' => 1], ['value' => 1]]])->once();
        $lpaCollection = Mockery::mock(MongoCollection::class);
        $lpaCollection->db = $lpaDb;
        $lpaCollection->shouldReceive('getName')->andReturn('test')->once();

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('remove')->once();
        $statsLpasCollection->shouldReceive('batchInsert')->andReturn(['ok' => true])->once();

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($lpaCollection);
        $serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-stats-lpas')->andReturn($statsLpasCollection);

        $stats = new Stats();
        $stats->setServiceLocator($serviceLocatorMock);

        $result = $stats->generate();

        $this->assertTrue($result);
    }
}