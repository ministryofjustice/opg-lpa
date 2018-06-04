<?php

namespace ApplicationTest\Model\Service\Stats;

use Application\Model\Service\Stats\Service;
use ApplicationTest\AbstractServiceTest;
use Auth\Model\Service\StatsService as AuthStatsService;
use DateTime;
use Mockery;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\ReadPreference;

class ServiceTest extends AbstractServiceTest
{
    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('findOne')
            ->withArgs([[], ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]])
            ->andReturn(['generated' => $generated]);

        $authStatsService = Mockery::mock(AuthStatsService::class);
        $authStatsService->shouldReceive('getStats')
            ->andReturn([
                'total' => 1,
            ]);

        /** @var MongoCollection $statsLpasCollection */
        /** @var AuthStatsService $authStatsService */
        $service = new Service($statsLpasCollection, $authStatsService);

        $data = $service->fetch('all');

        $this->assertEquals([
            'generated' => $generated,
            'users' => [
                'total' => 1,
            ],
        ], $data);
    }
}
