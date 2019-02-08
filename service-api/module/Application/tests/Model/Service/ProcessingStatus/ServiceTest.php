<?php

namespace Application\Model\Service\ProcessingStatus;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Hamcrest\Matchers;
use Http\Client\HttpClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

class ServiceTest extends MockeryTestCase
{
    /**
     * @var MockInterface| HttpClient
     */
    private $httpClient;

    /**
     * @var ResponseInterface|MockInterface
     */
    private $response;

    private $service;

    public function setUp()
    {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->service = new Service();
        $this->service->setClient($this->httpClient);
        $this->service->setConfig(['processing-status' => ['endpoint' => 'http://thing/processing-status/']]);
    }

    public function setUpRequest($returnStatus = 200,
        $returnBody = '{"found": true, "status": "Registered"}')
    {
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->response->shouldReceive('getStatusCode')->once()->andReturn($returnStatus);

        if ($returnBody != null) {
            $this->response->shouldReceive('getBody')->once()->andReturn($returnBody);
        }

        $this->httpClient->shouldReceive('sendRequest')
            ->withArgs(
                [Matchers::equalTo(
                    new Request(
                        'GET',
                        new Uri('http://thing/processing-status/12'),
                        ['Accept' => 'application/json', 'Content-type' => 'application/json']
                    )
                )]
            )
            ->once()
            ->andReturn($this->response);

    }

    public function testGetStatus()
    {

        $this->setUpRequest();

        $result = $this->service->getStatus(12);

        $this->assertEquals('Concluded', $result);


    }

    /**
     * @expectedException Application\Library\ApiProblem\ApiProblemException
     *
     */
    public function testGetStatus400()
    {
        $this->setUpRequest(400, null);

        $this->service->getStatus(12);
    }
}
