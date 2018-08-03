<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiWhoCollection;
use Application\Model\Service\System\Stats;
use Mockery;
use Opg\Lpa\Logger\Logger;
use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function testGenerate()
    {
        $apiLpaCollection = Mockery::mock(ApiLpaCollection::class);

        //Return 1 for all counts to aid mocking mongo calls
        $apiLpaCollection->shouldReceive('count')->andReturn(1);

        $manager = Mockery::mock();
        $cursor = Mockery::mock(\Traversable::class);
        $cursor->shouldReceive('toArray')->andReturn([0 => (object)['results' => [(object)['value' => 1], (object)['value' => 1]]]]);
        $manager->shouldReceive('executeCommand')->andReturn($cursor)->once();
        $apiLpaCollection->shouldReceive('getManager')->andReturn($manager)->once();
        $apiLpaCollection->shouldReceive('getDatabaseName')->andReturn('test')->once();
        $apiLpaCollection->shouldReceive('getCollectionName')->andReturn('test')->once();
        /** @var ApiLpaCollection $apiLpaCollection */

        $statsLpasCollection = Mockery::mock(ApiStatsLpasCollection::class);
        $statsLpasCollection->shouldReceive('delete')->once();
        $statsLpasCollection->shouldReceive('insert')->withArgs(function ($stats) {
            return isset($stats['generated'])
                && isset($stats['lpas'])
                && isset($stats['lpasPerUser'])
                && isset($stats['who'])
                && isset($stats['correspondence'])
                && isset($stats['preferencesInstructions']);
        })->once();
        /** @var ApiStatsLpasCollection $statsLpasCollection */

        $whoCollection = Mockery::mock(ApiWhoCollection::class);
        $whoCollection->shouldReceive('count')->andReturn(1);
        /** @var ApiWhoCollection $whoCollection */

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('err');
        /** @var Logger $logger */

        $stats = new Stats($apiLpaCollection, $statsLpasCollection, $whoCollection);
        $stats->setLogger($logger);

        $result = $stats->generate();

        $this->assertTrue($result);
    }
}