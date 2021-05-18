<?php

namespace Application\Model\Service\ProcessingStatus;

use Application\Library\ApiProblem\ApiProblemException;
use GuzzleHttp\Client;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Http\Client\Exception;
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

    /**
     * @var SignatureV4
     */
    private $awsSignature;

    public function setUp() : void
    {
        $this->httpClient = Mockery::mock(Client::class);
        $this->credentials = Mockery::mock(CredentialsInterface::class);
        $this->awsSignature = Mockery::mock(SignatureV4::class);

        $this->service = new Service();
        $this->service->setAwsSignatureV4($this->awsSignature);
        $this->service->setClient($this->httpClient);
        $this->service->setCredentials($this->credentials);
        $this->service->setConfig(['processing-status' => ['endpoint' => 'http://thing/processing-status/']]);
    }

    public function setUpSigning($timesCalled = 1)
    {
        // We want to return the GuzzleHttp\Psr7\Request which was passed in the first argument.
        $this->awsSignature->shouldReceive('signRequest')->times($timesCalled)->andReturnUsing(function($request){
            return $request;
        });
    }

    public function setUpRequest($returnStatus = 200,
                                 $returnBody = '{"status": "Pending","rejectedDate": null}')
    {
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->response->shouldReceive('getStatusCode')->once()->andReturn($returnStatus);

        if ($returnBody != null) {
            $this->response->shouldReceive('getBody')->andReturn($returnBody);
        }

        $this->httpClient->shouldReceive('sendAsync')
            ->once()
            ->andReturn($this->response);
    }

    /**
     * @throws ApiProblemException
     * @throws Exception
     */
    public function testGetStatuses()
    {
        $this->setUpSigning();
        $this->setUpRequest();

        $result = $this->service->getStatuses([1000000000]);

        $this->assertEquals([1000000000 => ['status' => 'Received']], $result);
    }

    /**
     * @throws ApiProblemException
     * @throws Exception
     */
    public function testGetStatusesForMultipleLpaIds()
    {
        $this->setUpSigning(2);
        $this->setUpRequest();
        $this->setUpRequest();

        $statusResult = $this->service->getStatuses([1000000000,1000000001]);

        $this->assertEquals(
            [
                1000000000 => ['status' => 'Received'],
                1000000001 => ['status' => 'Received']
            ], $statusResult
        );
    }

    /**
     * @throws ApiProblemException
     * @throws Exception
     */
    public function testGetStatuses400()
    {
        $this->expectException(ApiProblemException::class);

        $this->setUpSigning();

        $this->setUpRequest(400, '{}');

        $this->service->getStatuses([1000000000]);
    }

    /**
     * @throws ApiProblemException
     * @throws Exception
     */
    public function testGetStatusesNotFound()
    {
        $this->setUpSigning();

        $this->setUpRequest(404, null);

        $statusResultArray  = $this->service->getStatuses([1000000000]);

        $this->assertEquals(
            [
                1000000000 => null
            ]
            , $statusResultArray
        );
    }

    /**
     * @throws ApiProblemException
     * @throws Exception
     */
    public function testGetProcessedStatusForRejected()
    {
        $this->setUpSigning();
        $returnStatus = 200;
        $returnBody = '{"status": "Rejected","rejectedDate": "2019-02-11"}';
        $this->setUpRequest($returnStatus, $returnBody);
        $statusResult = $this->service->getStatuses([1000000000]);

        $this->assertEquals([1000000000 => ['status' => 'Processed', 'rejectedDate' => '2019-02-11']], $statusResult);
    }

    public function testGetProcessedStatusForWithdrawn()
    {
        $this->setUpSigning();
        $returnStatus = 200;
        $returnBody = '{"status": "Withdrawn","withdrawnDate": "2021-03-08"}';
        $this->setUpRequest($returnStatus, $returnBody);
        $statusResult = $this->service->getStatuses([1000000000]);

        $this->assertEquals([1000000000 => ['status' => 'Processed', 'withdrawnDate' => '2021-03-08']], $statusResult);
    }

    public function testGetProcessedStatusForInvalid()
    {
        $this->setUpSigning();
        $returnStatus = 200;
        $returnBody = '{"status": "Invalid","invalidDate": "2021-02-08"}';
        $this->setUpRequest($returnStatus, $returnBody);
        $statusResult = $this->service->getStatuses([1000000000]);

        $this->assertEquals([1000000000 => ['status' => 'Processed', 'invalidDate' => '2021-02-08']], $statusResult);
    }
}
