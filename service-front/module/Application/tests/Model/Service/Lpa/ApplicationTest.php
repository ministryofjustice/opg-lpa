<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Response\Lpa;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
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
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(200)->once();
        $mockResponse->shouldReceive('getBody')->andReturn('{}')->once();

        $this->apiClient->shouldReceive('httpGet')->andReturn($mockResponse)->once();

        $result = $this->service->getApplication(1234);

        $expectedResult = new Lpa();
        $expectedResult->setResponse($mockResponse);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationWithNewToken()
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(200)->once();
        $mockResponse->shouldReceive('getBody')->andReturn('{}')->once();

        $this->apiClient->shouldReceive('httpGet')->andReturn($mockResponse)->once();
        $this->apiClient->shouldReceive('updateToken')->withArgs(['new token'])->once();

        $result = $this->service->getApplication(1234, 'new token');

        $expectedResult = new Lpa();
        $expectedResult->setResponse($mockResponse);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationFailure()
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400)->once();

        $this->apiClient->shouldReceive('httpGet')->andReturn($mockResponse)->once();

        $result = $this->service->getApplication(1234);

        $this->assertFalse($result);
    }

}
