<?php

namespace ApplicationTest\Model\Service\Stats;

use Application\Model\DataAccess\Repository\Stats\StatsRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Stats\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use DateTime;
use Mockery;

class ServiceTest extends AbstractServiceTestCase
{
    public function testFetch()
    {
        $generated = date('d/m/Y H:i:s', (new DateTime())->getTimestamp());

        $statsRepository = Mockery::mock(StatsRepositoryInterface::class);
        $statsRepository->shouldReceive('getStats')->andReturn(['generated' => $generated]);

        $authUserRepository = Mockery::mock(UserRepositoryInterface::class);
        $authUserRepository->shouldReceive('countAccounts')->once()->andReturn(4);
        $authUserRepository->shouldReceive('countActivatedAccounts')
            ->withArgs([])->once()->andReturn(3);
        $authUserRepository->shouldReceive('countActivatedAccounts')
            ->withArgs(function ($since) {
                return $since == new DateTime('first day of this month 00:00:00');
            })
            ->once()->andReturn(2);
        $authUserRepository->shouldReceive('countDeletedAccounts')->once()->andReturn(1);

        $service = new Service();
        $service->setUserRepository($authUserRepository);
        $service->setStatsRepository($statsRepository);

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
