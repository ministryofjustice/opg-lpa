<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result as AwsResult;
use DateTime;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Constants;
use Mockery;
use Mockery\MockInterface;

class StatusTest extends AbstractServiceTest
{
    /**
     * @var Client|MockInterface
     */
    private $apiClient;

    /**
     * @var DynamoDbClient|MockInterface
     */
    private $dynamoDbSessionClient;

    /**
     * @var DynamoDbClient|MockInterface
     */
    private $dynamoDbClient;

    /**
     * @var SaveHandlerInterface|MockInterface
     */
    private $sessionSaveHandler;

    /**
     * @var RedisClient|MockInterface
     */
    private $redisClient;


    /**
     * @var OrdnanceSurvey|MockInterface
     */
    private $ordnanceSurveyClient;


    /**
     * @var $service Status
     */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'admin' => [
                'dynamodb' => [
                    'settings' => [
                        'table_name' => 'admin-test-table',
                    ]
                ]
            ],
            'redis' => [
                'url' => 'test-url',
                //'ttlMs' => (1000 * 60 * 60 * 3),
                'ordnance_survey' => [
                    'max_call_per_min' => 2,
                ],
            ]
        ];

        $this->service = new Status($this->authenticationService, $config);

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);

        $this->dynamoDbClient = Mockery::mock(DynamoDbClient::class);
        $this->service->setDynamoDbClient($this->dynamoDbClient);

        $this->redisClient = Mockery::mock(RedisClient::class);
        $this->service->setRedisClient($this->redisClient);

        $this->sessionSaveHandler = Mockery::mock(SaveHandlerInterface::class);
        $this->service->setSessionSaveHandler($this->sessionSaveHandler);

        $this->ordnanceSurveyClient = Mockery::mock(OrdnanceSurvey::class);
        $this->service->setOrdnanceSurveyClient($this->ordnanceSurveyClient);
    }

    public function testCheckAllOk(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->times(1)->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->once()->andReturn('');
        $this->redisClient->shouldReceive('write')->times(3)->andReturn(true);
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn($expectedOsResponse);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => 200,
                ],
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => false,
                'details' => [
                    'line1' => 'SOME PALACE',
                    'line2' => 'SOME CITY',
                    'line3' => '',
                    'postcode' => 'SOME POSTCODE',
                ]
            ],
            'ok' => true,
            'status' => Constants::STATUS_PASS,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckDynamoInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->once()
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 500]]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->once()->andReturn('');
        $this->redisClient->shouldReceive('write')->times(3)->andReturn(true);
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn($expectedOsResponse);
        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => '200',
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => false,
                'details' => [
                    'line1' => 'SOME PALACE',
                    'line2' => 'SOME CITY',
                    'line3' => '',
                    'postcode' => 'SOME POSTCODE',
                ]
            ],
            'ok' => true,
            'status' => Constants::STATUS_WARN,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckApiInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(6)
            ->andReturn([
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(6)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->times(6)->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->once()->andReturn('');
        $this->redisClient->shouldReceive('write')->times(3)->andReturn(true);
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn($expectedOsResponse);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
                'details' => [
                    'response_code' => 200,
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => false,
                'details' => [
                    'line1' => 'SOME PALACE',
                    'line2' => 'SOME CITY',
                    'line3' => '',
                    'postcode' => 'SOME POSTCODE',
                ]
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
            'iterations' => 6,
        ], $result);
    }

    public function testCheckApiWarn(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_WARN,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->times(1)->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->once()->andReturn('');
        $this->redisClient->shouldReceive('write')->times(3)->andReturn(true);
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn($expectedOsResponse);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_WARN,
                'details' => [
                    'response_code' => 200,
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => false,
                'details' => [
                    'line1' => 'SOME PALACE',
                    'line2' => 'SOME CITY',
                    'line3' => '',
                    'postcode' => 'SOME POSTCODE',
                ]
            ],
            'ok' => true,
            'status' => Constants::STATUS_WARN,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckSessionSaveHandlerInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->andReturn(false);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->andReturn(true);
        $this->redisClient->shouldReceive('read')->andReturn('');
        $this->redisClient->shouldReceive('write')->andReturn(true);
        $this->redisClient->shouldReceive('close')->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->andReturn($expectedOsResponse);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => 200,
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => false,
                'details' => [
                    'line1' => 'SOME PALACE',
                    'line2' => 'SOME CITY',
                    'line3' => '',
                    'postcode' => 'SOME POSTCODE',
                ]
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
            'iterations' => 6,
        ], $result);
    }

    public function testCheckOrdnanceSurveyInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->times(1)->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->once()->andReturn('');
        $this->redisClient->shouldReceive('write')->times(3)->andReturn(true);
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn(false);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => '200',
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
                'cached' => false,
                'details' => ''
            ],
            // Unlike the other tests, when os fails it doesn't fail the overall health check as it's not vital to the service
            'ok' => true,
            'status' => Constants::STATUS_WARN,

            'iterations' => 1,
        ], $result);
    }

    public function testCheckOrdnanceSurveyusesCache(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->times(1)->andReturn(true);

        // mock os_last_call to within the last second
        $currentTimestamp = (new DateTime('now'))->getTimestamp();
        $osLastCall = $currentTimestamp - 1;

        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->with('os_last_call')->once()->andReturn($osLastCall);
        $this->redisClient->shouldReceive('read')->with('os_last_status')->once()->andReturn('true');
        $this->redisClient->shouldReceive('read')->with('os_last_details')->once()->andReturn('{"foo": "bar"}');
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'api' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => '200',
                ]
            ],
            'sessionSaveHandler' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ordnanceSurvey' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'cached' => true,
                'details' => ['foo' => 'bar'],
            ],

            'ok' => true,
            'status' => Constants::STATUS_PASS,

            'iterations' => 1,
        ], $result, 'OS call within 1 second of previous call should return cached details');
    }
}
