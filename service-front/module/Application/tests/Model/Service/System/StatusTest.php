<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\DynamoDb\DynamoDbClient;
use Mockery;
use Mockery\MockInterface;

class StatusTest extends AbstractServiceTest
{
    /**
     * @var $apiClient Client|MockInterface
     */
    private $apiClient;

    /**
     * @var $dynamoDbSessionClient DynamoDbClient|MockInterface
     */
    private $dynamoDbSessionClient;

    /**
     * @var dynamoDbCronClient DynamoDbClient|MockInterface
     */
    private $dynamoDbCronClient;

    /**
     * @var $service Status
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $config = [
            'session'=>[
                'dynamodb'=>[
                    'settings'=>[
                        'table_name'=>"session-test-table"
                    ]
                ]
            ],
            'cron' =>[
                'lock' =>[
                    'dynamodb' =>[
                        'settings' =>[
                            'table_name'=>"cron-test-table"
                        ]
                    ]
                ]
            ]
        ];

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Status($this->authenticationService, $config);
        $this->service->setApiClient($this->apiClient);

        $this->dynamoDbSessionClient = Mockery::mock(DynamoDbClient::class);
        $this->dynamoDbCronClient = Mockery::mock(DynamoDbClient::class);
        $this->service->setDynamoDbSessionClient($this->dynamoDbSessionClient);
        $this->service->setDynamoDbCronClient($this->dynamoDbCronClient);
    }

    public function testCheckAllOk() : void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(6)
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbSessionClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'session-test-table']])
            ->times(6)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $this->dynamoDbCronClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'cron-test-table']])
            ->times(6)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'details' => [
                    'sessions' => true,
                    'locks' => true
                ]
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    200 => true,
                    'ok' => true,
                ]
            ],
            'ok' => true,
            'iterations' => 6,
        ], $result);
    }

    public function testCheckInvalidSession() : void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbSessionClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'session-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 500]]);

        $this->dynamoDbCronClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'cron-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $result = $this->service->check();


        $this->assertEquals([
            'dynamo' => [
                'ok' => false,
                'details' => [
                    'sessions' => false,
                    'locks' => true
                ]
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    200 => true,
                    'ok' => true,
                ]
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckInvalidCron() : void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbSessionClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'session-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $this->dynamoDbCronClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'cron-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 500]]);

        $result = $this->service->check();


        $this->assertEquals([
            'dynamo' => [
                'ok' => false,
                'details' => [
                    'sessions' => true,
                    'locks' => false
                ]
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    200 => true,
                    'ok' => true,
                ]
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckApiInvalid() : void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => false,
            ]);

        $this->dynamoDbSessionClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'session-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $this->dynamoDbCronClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'cron-test-table']])
            ->times(1)
            ->andReturn(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
                'details' => [
                    'sessions' => true,
                    'locks' => true
                ]
            ],
            'api' => [
                'ok' => false,
                'details' => [
                    200 => true,
                    'ok' => false,
                ]
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }
}
