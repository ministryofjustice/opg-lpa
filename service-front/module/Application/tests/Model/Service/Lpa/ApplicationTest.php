<?php

declare(strict_types=1);

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
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;

final class ApplicationTest extends MockeryTestCase
{
    private MockInterface|AuthenticationService $authenticationService;
    private MockInterface|Client $apiClient;
    private Application $service;

    private function modifiedLPA(int $id = 5531003157, $completedAt = null, $processingStatus = null, $rejectedDate = null)
    {
        $decodeJsonAsArray = true;
        $arr = json_decode(FixturesData::getHwLpaJson(), $decodeJsonAsArray);

        $arr['id'] = $id;

        if ($completedAt != null) {
            $arr['completedAt'] = $completedAt;
        }

        if ($processingStatus) {
            $arr['metadata'][Lpa::SIRIUS_PROCESSING_STATUS] = $processingStatus;
        }

        if ($rejectedDate != null) {
            $arr['metadata'][Lpa::APPLICATION_REJECTED_DATE] = $rejectedDate;
        }

        return $arr;
    }

    public function setUp(): void
    {
        $logger = Mockery::spy(LoggerInterface::class);
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->andReturn(4321);

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity);

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Application(
            $this->apiClient,
            $this->authenticationService,
            $logger,
            [
                'processing-status' => ['track-from-date' => '2019-01-01']
            ],
        );
    }

    public function testGetApplication(): void
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();

        $result = $this->service->getApplication(1234);

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationWithNewToken(): void
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();
        $this->apiClient->shouldReceive('updateToken')->withArgs(['new token'])->once();

        $result = $this->service->getApplication(1234, 'new token');

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationFailure(): void
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn(Utils::streamFor('{}'))->once();

        $this->apiClient->shouldReceive('httpGet')->andThrow(new ApiException($mockResponse));

        $result = $this->service->getApplication(1234);

        $this->assertFalse($result);
    }

    public function testGetStatuses(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(['4321' => ['found' => true, 'status' => 'Concluded']]);

        $result = $this->service->getStatuses(4321);

        $this->assertEquals(['4321' => ['found' => true, 'status' => 'Concluded']], $result);
    }

    public function testGetStatusesNull(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(null);

        $result = $this->service->getStatuses('4321');

        $this->assertEquals(['4321' => ['found' => false]], $result);
    }

    public function testGetStatusesException(): void
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn(Utils::streamFor('{}'))->once();
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $result = $this->service->getStatuses(4321);

        $this->assertEquals(['4321' => ['found' => false]], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesNoApplications(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => []]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [],'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesMultipleApplications(): void
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
                'refreshId' => null,
                'isReusable' => true
            ]),
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Completed',
                'rejectedDate' => null,
                'refreshId' => null,
                'isReusable' => true
            ])
        ], 'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesCanTrackWithNoTrackingStatus(): void
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
                'refreshId' => 5531003157,
                'isReusable' => true
            ])
        ], 'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesCanTrackWithTrackingStatus(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => [$this->modifiedLPA(5531003157, '2019-01-2', 'Processed', '2019-01-2')]]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Processed',
                'rejectedDate' => '2019-01-2',
                'refreshId' => 5531003157,
                'isReusable' => true
            ])
        ], 'trackingEnabled' => true], $result);
    }

    public function testAttorneyOverflowGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human']
                ]
            ]
        ]);

        $this->assertEquals(
            ['PRIMARY_ATTORNEY_OVERFLOW', 'ANY_PEOPLE_OVERFLOW'],
            $this->service->getContinuationNoteKeys($mockLpa)
        );
    }

    public function testAnyOverflowGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'peopleToNotify' => [[], [], [], [], []]
            ]
        ]);

        $this->assertEquals(
            ['NOTIFY_OVERFLOW', 'ANY_PEOPLE_OVERFLOW'],
            $this->service->getContinuationNoteKeys($mockLpa)
        );
    }

    public function testLongInstructionGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'instruction' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                                  incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                                  exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure
                                  dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
                                  Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
                                  mollit anim id est laborum.'
            ]
        ]);

        $this->assertEquals(['LONG_INSTRUCTIONS_OR_PREFERENCES'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testCantSignGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'donor' => [
                    'canSign' => false
                ]
            ]
        ]);

        $this->assertEquals(['CANT_SIGN'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testTrustAttorneyGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'corporation', 'number' => '123']
                ]
            ]
        ]);

        $this->assertEquals(['HAS_TRUST_CORP'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testCombinationsGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'preference' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                                incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                                exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure
                                dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
                                Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
                                mollit anim id est laborum.',
                'replacementAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                ],
                'primaryAttorneyDecisions' => [
                    'howDetails' => 'Decisions must be made at midnight'
                ]
            ]
        ]);

        $expectedResult = ['LONG_INSTRUCTIONS_OR_PREFERENCES',
                           'REPLACEMENT_ATTORNEY_OVERFLOW',
                           'ANY_PEOPLE_OVERFLOW',
                           'HAS_ATTORNEY_DECISIONS'];
        $this->assertEqualsCanonicalizing($expectedResult, $this->service->getContinuationNoteKeys($mockLpa));
    }
}
