<?php

namespace ApplicationTest\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Mockery;

class ServiceTest extends AbstractServiceTest
{
    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $apiStatsLpasCollection = Mockery::mock(ApiStatsLpasCollection::class);
        $apiStatsLpasCollection->shouldReceive('getStats')
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

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiStatsLpasCollection($apiStatsLpasCollection)
            ->withAuthUserCollection($authUserCollection)
            ->build();

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
