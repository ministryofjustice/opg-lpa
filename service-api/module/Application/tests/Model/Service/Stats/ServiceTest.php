<?php

namespace ApplicationTest\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\Service\Stats\Service;
use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\Driver\ReadPreference;

class ServiceTest extends MockeryTestCase
{
    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $statsLpasCollection = Mockery::mock(ApiStatsLpasCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('findOne')
            ->withArgs([[], ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]])
            ->andReturn(['generated' => $generated]);

        $authUserCollection = Mockery::mock(AuthUserCollection::class);
        $authUserCollection->shouldReceive('countAccounts')->once()->andReturn(4);
        $authUserCollection->shouldReceive('countActivatedAccounts')
            ->withArgs([])->once()->andReturn(3);
        $authUserCollection->shouldReceive('countActivatedAccounts')
            ->withArgs(function ($since) {
                return $since == new DateTime('first day of this month 00:00:00');
            })
            ->once()->andReturn(2);
        $authUserCollection->shouldReceive('countDeletedAccounts')->once()->andReturn(1);

        /** @var ApiStatsLpasCollection $statsLpasCollection */
        /** @var AuthUserCollection $authUserCollection */
        $service = new Service($statsLpasCollection, $authUserCollection);

        $data = $service->fetch('all');

        $this->assertEquals([
            'generated' => $generated,
            'users' => [
                'total' => 4,
                'activated' => 3,
                'activated-this-month' => 2,
                'deleted' => 1,
            ],
        ], $data);
    }
}
