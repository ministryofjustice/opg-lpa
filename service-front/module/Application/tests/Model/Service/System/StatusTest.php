<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result as AwsResult;
use DateTime;
use Exception;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Constants;
use Mockery;
use Mockery\MockInterface;

final class StatusTest extends AbstractServiceTest
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
     * @var MailTransportInterface|MockInterface
     */
    private $mailTransport;


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

        $this->mailTransport = Mockery::mock(MailTransportInterface::class);
        $this->service->setMailTransport($this->mailTransport);
    }

    public function testCheckAllOk(): void
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
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => true,
            'status' => Constants::STATUS_PASS,
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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => true,
            'status' => Constants::STATUS_WARN,
        ], $result);
    }

    public function testCheckDynamoException(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->andThrow(new Exception('unexpected dynamodb exception'));

        $this->sessionSaveHandler->shouldReceive('open')->andReturn(true);

        $this->redisClient->shouldReceive('open')->andReturn(true);
        $this->redisClient->shouldReceive('read')->andReturn('');
        $this->redisClient->shouldReceive('write')->andReturn(true);
        $this->redisClient->shouldReceive('close')->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->andReturn($expectedOsResponse);

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => true,
            'status' => Constants::STATUS_WARN,
        ], $result);
    }

    public function testCheckApiInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->once()
            ->andReturn([
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
        ], $result);
    }

    public function testCheckApiWarn(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->once()
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_WARN,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => true,
            'status' => Constants::STATUS_WARN,
        ], $result);
    }

    public function testCheckApiException(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->andThrow(new Exception('unexpected HTTP failure'));

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->andReturn(true);

        $this->redisClient->shouldReceive('open')->andReturn(true);
        $this->redisClient->shouldReceive('read')->andReturn('');
        $this->redisClient->shouldReceive('write')->andReturn(true);
        $this->redisClient->shouldReceive('close')->andReturn(true);

        $expectedOsResponse = [[
                'line1' => 'SOME PALACE',
                'line2' => 'SOME CITY',
                'line3' => '',
                'postcode' => 'SOME POSTCODE',
        ]];
        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->once()->andReturn($expectedOsResponse);
        $this->ordnanceSurveyClient->shouldReceive('verify')->once()->andReturn($expectedOsResponse);

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
                    'response_code' => 500,
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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
        ], $result);
    }

    public function testCheckOrdnanceSurveyInvalid(): void
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
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(true);

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

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],
            // Unlike the other tests, when os fails it doesn't fail the overall health check as it's not vital to the service
            'ok' => true,
            'status' => Constants::STATUS_WARN,
        ], $result);
    }

    public function testCheckOrdnanceSurveyusesCache(): void
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
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(true);

        // mock os_last_call to within the last second
        $currentTimestamp = (new DateTime('now'))->getTimestamp();
        $osLastCall = $currentTimestamp - 1;

        $this->redisClient->shouldReceive('open')->once()->andReturn(true);
        $this->redisClient->shouldReceive('read')->with('os_last_call')->once()->andReturn($osLastCall);
        $this->redisClient->shouldReceive('read')->with('os_last_status')->once()->andReturn('true');
        $this->redisClient->shouldReceive('read')->with('os_last_details')->once()->andReturn('{"foo": "bar"}');
        $this->redisClient->shouldReceive('close')->once()->andReturn(true);

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

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
            'mail' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ],

            'ok' => true,
            'status' => Constants::STATUS_PASS,
        ], $result, 'OS call within 1 second of previous call should return cached details');
    }

    public function testCheckMailStatusPass(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->andReturn(true);

        $this->redisClient->shouldReceive('open')->andReturn(true);
        $this->redisClient->shouldReceive('read')->andReturn('');
        $this->redisClient->shouldReceive('write')->andReturn(true);
        $this->redisClient->shouldReceive('close')->andReturn(true);

        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->andReturn([[]]);
        $this->ordnanceSurveyClient->shouldReceive('verify')->andReturn(true);

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => true, 'status' => Constants::STATUS_PASS]);

        $result = $this->service->check();

        $this->assertEquals(['ok' => true, 'status' => Constants::STATUS_PASS], $result['mail']);
        $this->assertTrue($result['ok']);
    }

    public function testCheckMailStatusFail(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->andReturn([
                'ok' => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->andReturn(true);

        $this->redisClient->shouldReceive('open')->andReturn(true);
        $this->redisClient->shouldReceive('read')->andReturn('');
        $this->redisClient->shouldReceive('write')->andReturn(true);
        $this->redisClient->shouldReceive('close')->andReturn(true);

        $this->ordnanceSurveyClient->shouldReceive('lookupPostcode')->andReturn([[]]);
        $this->ordnanceSurveyClient->shouldReceive('verify')->andReturn(true);

        $this->mailTransport->shouldReceive('healthcheck')
            ->andReturn(['ok' => false, 'status' => Constants::STATUS_FAIL]);

        $result = $this->service->check();

        $this->assertEquals(['ok' => false, 'status' => Constants::STATUS_FAIL], $result['mail']);

        // Failing mail service causes the whole status to be 'fail'
        $this->assertFalse($result['ok']);
    }
}
