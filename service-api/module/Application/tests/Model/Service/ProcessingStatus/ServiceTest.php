<?php

namespace Application\Model\Service\ProcessingStatus;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Aws\Signature\SignatureV4;
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

    /**
     * @var Service
     */
    private $service;

    public function setUp()
    {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $awsSignature = Mockery::mock(SignatureV4::class);

        // We want to return the GuzzleHttp\Psr7\Request which was passed in teh first argument.
        $awsSignature->shouldReceive('signRequest')->once()->andReturnUsing(function($request){
            return $request;
        });

        $this->service = new Service();
        $this->service->setAwsSignatureV4($awsSignature);
        $this->service->setClient($this->httpClient);
        $this->service->setConfig(['processing-status' => ['endpoint' => 'http://thing/processing-status/']]);
    }

    public function setUpRequest($returnStatus = 200,
        $returnBody = '{"status": "Pending"}')
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
                        new Uri('http://thing/processing-status/A01000000000'),
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

        $result = $this->service->getStatus(1000000000);

        $this->assertEquals('Received', $result);
    }

    /**
     * @expectedException Application\Library\ApiProblem\ApiProblemException
     *
     */
    public function testGetStatus400()
    {
        $this->setUpRequest(400, '{}');

        $this->service->getStatus(1000000000);
    }

    public function testGetStatusNotFound()
    {
        $this->setUpRequest(404, null);

        $result = $this->service->getStatus(1000000000);
        $this->assertNull($result);
    }

}
