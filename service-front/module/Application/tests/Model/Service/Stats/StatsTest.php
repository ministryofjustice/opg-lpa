<?php

namespace ApplicationTest\Model\Service\Stats;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Stats\Stats;
use ApplicationTest\Model\Service\AbstractServiceTest;
use ApplicationTest\Model\Service\ServiceTestHelper;
use Mockery;
use Mockery\MockInterface;

class StatsTest extends AbstractServiceTest
{
    /**
     * @var $apiClient Client|MockInterface
     */
    private $apiClient;

    /**
     * @var $service Stats
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(Client::class);
        
        $this->service = new Stats($this->authenticationService, []);
        $this->service->setApiClient($this->apiClient);
    }

    public function testGetApiStats() : void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/stats/all'])
            ->once()
            ->andReturn(['test' => 'stats']);

        $result = $this->service->getApiStats();

        $this->assertEquals(['test' => 'stats'], $result);
    }

    public function testGetApiStatsApiException() : void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/stats/all'])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->getApiStats();

        $this->assertFalse($result);
    }
}
