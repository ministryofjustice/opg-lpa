<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\Stats;
use Mockery;
use MongoDB\Collection as MongoCollection;
use Opg\Lpa\Logger\Logger;
use PHPUnit\Framework\TestCase;

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
        /** @var MongoCollection $lpaCollection */

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
        /** @var MongoCollection $statsLpasCollection */

        $whoCollection = Mockery::mock(MongoCollection::class);
        $whoCollection->shouldReceive('count')->andReturn(1);
        /** @var MongoCollection $whoCollection */

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('err');
        /** @var Logger $logger */

        $stats = new Stats($lpaCollection, $statsLpasCollection, $whoCollection);
        $stats->setLogger($logger);

        $result = $stats->generate();

        $this->assertTrue($result);
    }
}