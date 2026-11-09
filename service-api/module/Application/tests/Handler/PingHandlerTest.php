<?php

declare(strict_types=1);

namespace ApplicationTest\Handle;

use Application\Handler\PingHandler;
use Aws\Sqs\SqsClient;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class PingHandlerTest extends MockeryTestCase
{
    private MockInterface&DbAdapter $database;
    private MockInterface&SqsClient $sqsClient;
    private MockInterface&ClientInterface $httpClient;
    private MockInterface&LoggerInterface $logger;

    private PingHandler $handler;

    public function setUp(): void
    {
        $this->database           = Mockery::mock(DbAdapter::class);
        $this->sqsClient          = Mockery::mock(SqsClient::class);
        $this->httpClient         = Mockery::mock(ClientInterface::class);
        $this->logger             = Mockery::mock(LoggerInterface::class);

        $this->handler = new PingHandler(
            $this->database,
            $this->sqsClient,
            'http://test',
            $this->logger,
        );
    }

    public function testItReturnsAllServiceOk(): void
    {
        // good response from SQS
        $this->sqsClient->allows('getQueueAttributes')
            ->andReturns([
                'Attributes' => [
                    'ApproximateNumberOfMessages' => 1,
                    'ApproximateNumberOfMessagesNotVisible' => 2,
                ]
            ]);

        // good response from db
        $mockConnection = Mockery::mock(ConnectionInterface::class);
        $mockConnection->allows('connect')->andReturns($mockConnection);

        $mockDriver = Mockery::mock(DriverInterface::class);
        $mockDriver->allows('getConnection')->andReturns($mockConnection);

        $this->database->allows('getDriver')->andReturns($mockDriver);

        // ping response JSON we expect
        $expectedResult = [
            'database' => [
                'ok' => true,
            ],
            'ok' => true,
            'queue' => [
                'details' => [
                    'available' => true,
                    'length' => 3,
                    'lengthAcceptable' => true,
                ],
                'ok' => true,
            ],
        ];

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }

    public function testItReturnsDBandGatewayDown(): void
    {
        $this->sqsClient->allows('getQueueAttributes')
            ->andReturns([
                'Attributes' => [
                    'ApproximateNumberOfMessages' => 1,
                    'ApproximateNumberOfMessagesNotVisible' => 2,
                ]
            ]);

        $expectedResult = [
            'ok' => false,
            'database' => [
                'ok' => false,
            ],
            'queue' => [
                'ok' => true,
                'details' => [
                    'available' => true,
                    'length' => 3,
                    'lengthAcceptable' => true,
                ],
            ],
        ];

        $this->logger->expects('error');

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }

    public function testItReturnsQueueDownAndDatabaseException(): void
    {
        // bad response from SQS, so we throw an exception
        $this->sqsClient->allows('getQueueAttributes')
            ->andReturns([]);

        // ping response JSON we expect
        $expectedResult = [
            'database' => [
                'ok' => false,
            ],
            'ok' => false,
            'queue' => [
                'details' => [
                    'available' => false,
                    'length' => null,
                    'lengthAcceptable' => false,
                ],
                'ok' => false,
            ],
        ];

        $this->logger->expects('error')
            ->times(2);

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }
}
