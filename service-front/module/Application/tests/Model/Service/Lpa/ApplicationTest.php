<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use ArrayObject;
use DateTime;
use Http\Client\Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
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

        $this->service = new Application($this->authenticationService, [
            'processing-status' => ['track-from-date' => '2019-01-01']
        ]);
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

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesNoApplications()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => []]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [],'trackingEnabled' => true], $result);
    }

    private function modifiedLPA($id = 5531003157, $completedAt = null, $processingStatus = null, $rejectedDate = null)
    {
        $lpaJson = FixturesData::getHwLpaJson();

        $lpaJson = str_replace('"id" : 5531003156', '"id" : ' . $id, $lpaJson);

        if ($completedAt != null) {
            $lpaJson = str_replace(
                '"completedAt" : "2017-03-24T16:21:52.804Z"',
                '"completedAt" : "' . $completedAt . '"',
                $lpaJson
            );
        }

        if ($processingStatus) {
            $lpaJson = str_replace(
                '"metadata" : {',
                '"metadata" : {
                "' . Lpa::SIRIUS_PROCESSING_STATUS . '" : "' . $processingStatus . '",',
                $lpaJson
            );
        }

        if ($rejectedDate != null) {
            $lpaJson = str_replace(
                '"metadata" : {',
                '"metadata" : {
                 "' . Lpa::APPLICATION_REJECTED_DATE . '" : "' . $rejectedDate . '",',
                $lpaJson
            );
        }


        return $lpaJson;
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesMultipleApplications()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => [FixturesData::getHwLpaJson(), $this->modifiedLPA()]]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [
            new ArrayObject([
                'id' => 5531003156,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Completed',
                'rejectedDate' => null,
                'refreshId' => null
            ]),
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Completed',
                'rejectedDate' => null,
                'refreshId' => null
            ])
        ], 'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesCanTrackWithNoTrackingStatus()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => [$this->modifiedLPA(5531003157, '2019-01-02', 'Waiting')]]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Waiting',
                'rejectedDate' => null,
                'refreshId' => 5531003157
            ])
        ], 'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesCanTrackWithTrackingStatus()
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => [$this->modifiedLPA(5531003157, '2019-01-2', 'Returned', '2019-01-2')]]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Returned',
                'rejectedDate' => '2019-01-2',
                'refreshId' => 5531003157
            ])
        ], 'trackingEnabled' => true], $result);
    }
}
