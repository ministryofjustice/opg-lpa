<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result as AwsResult;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
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
            ]
        ];

        $this->service = new Status($this->authenticationService, $config);

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);

        $this->dynamoDbClient = Mockery::mock(DynamoDbClient::class);
        $this->service->setDynamoDbClient($this->dynamoDbClient);

        $this->sessionSaveHandler = Mockery::mock(SaveHandlerInterface::class);
        $this->service->setSessionSaveHandler($this->sessionSaveHandler);
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

        $this->sessionSaveHandler->shouldReceive('open')->times(6)->andReturn(true);

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
            'sessionSaveHandler' => [
                'ok' => true,
            ],
            'ok' => true,
            'iterations' => 6,
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
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 500]]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(true);

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
            'sessionSaveHandler' => [
                'ok' => true,
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
            ->once()
            ->andReturn([
                'ok' => false,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(true);

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
            'sessionSaveHandler' => [
                'ok' => true,
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }

    public function testCheckSessionSaveHandlerInvalid(): void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->once()
            ->andReturn([
                'ok' => true,
            ]);

        $this->dynamoDbClient
            ->shouldReceive('describeTable')
            ->withArgs([['TableName' => 'admin-test-table']])
            ->once()
            ->andReturn(new AwsResult(['@metadata' => ['statusCode' => 200],'Table' => ['TableStatus' => 'ACTIVE']]));

        $this->sessionSaveHandler->shouldReceive('open')->once()->andReturn(false);

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
            'sessionSaveHandler' => [
                'ok' => false,
            ],
            'ok' => false,
            'iterations' => 1,
        ], $result);
    }
}
