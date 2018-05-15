<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\StatsService;
use DateTime;

class StatsServiceTest extends ServiceTestCase
{
    /**
     * @var StatsService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new StatsService($this->userDataSource, $this->logDataSource);
    }

    public function testGetStats()
    {
        $this->userDataSource->shouldReceive('countAccounts')->once()->andReturn(4);
        $this->userDataSource->shouldReceive('countActivatedAccounts')
            ->withArgs([])->once()->andReturn(3);
        $this->userDataSource->shouldReceive('countActivatedAccounts')
            ->withArgs(function ($since) {
                return $since == new DateTime('first day of this month 00:00:00');
            })
            ->once()->andReturn(2);
        $this->userDataSource->shouldReceive('countDeletedAccounts')->once()->andReturn(1);

        $result = $this->service->getStats();

        $this->assertEquals([
            'total' => 4,
            'activated' => 3,
            'activated-this-month' => 2,
            'deleted' => 1
        ], $result);
    }
}
