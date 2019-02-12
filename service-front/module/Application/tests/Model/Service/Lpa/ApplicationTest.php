<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;

class ApplicationTest extends MockeryTestCase
{
    /**
     * @var MockInterface|AuthenticationService
     */
    private $authenticationService;

    /**
     * @var MockInterface|Client
     */
    private $apiClient;

    /**
     * @var Application
     */
    private $service;

    public function setUp() : void
    {
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->andReturn(4321);

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity);

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Application($this->authenticationService, []);
        $this->service->setApiClient($this->apiClient);
    }

    public function testGetApplication()
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();

        $result = $this->service->getApplication(1234);

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationWithNewToken()
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();
        $this->apiClient->shouldReceive('updateToken')->withArgs(['new token'])->once();

        $result = $this->service->getApplication(1234, 'new token');

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationFailure()
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn('{}')->once();

        $this->apiClient->shouldReceive('httpGet')->andThrow(new ApiException($mockResponse));

        $result = $this->service->getApplication(1234);

        $this->assertFalse($result);
    }

    public function testGetStatuses()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(['4321' => ['found'=>true, 'status'=>'Concluded']]);

        $result = $this->service->getStatuses(4321);

        $this->assertEquals(['4321' => ['found'=>true, 'status'=>'Concluded']], $result);
    }

    public function testGetStatusesNull()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(null);

        $result = $this->service->getStatuses('4321');

        $this->assertEquals(['4321' => ['found'=>false]], $result);

    }

    public function testGetStatusesException()
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn('{}')->once();
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $result = $this->service->getStatuses(4321);

        $this->assertEquals(['4321' => ['found'=>false]], $result);
    }
}
