<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result as AwsResult;
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

    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'admin' => [
                'dynamodb' => [
                    'settings' => [
                        'table_name' => "admin-test-table"
                    ]
                ]
            ]
        ];

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Status($this->authenticationService, $config);
        $this->service->setApiClient($this->apiClient);

        $this->dynamoDbClient = Mockery::mock(DynamoDbClient::class);
        $this->service->setDynamoDbClient($this->dynamoDbClient);
    }

    public function testCheckAllOk(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(6)
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(6)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    'status' => 200,
                ]
            ],
            'ok' => true,
            'iterations' => 6,
        ], $result);
    }

    public function testCheckInvalidCron(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 500]]));

        $result = $this->service->check();


        $this->assertEquals([
            'dynamo' => [
                'ok' => false,
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    'status' => '200',
                ]
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckApiInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(1)
            ->andReturn([
                'ok' => false,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->times(1)
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => true,
            ],
            'api' => [
                'ok' => false,
                'details' => [
                    'status' => 200,
                ]
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }
}
