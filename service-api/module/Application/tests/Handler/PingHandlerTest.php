<?php

declare(strict_types=1);

namespace ApplicationTest\Handle;

use Application\Handler\PingHandler;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Aws\Sqs\SqsClient;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class PingHandlerTest extends MockeryTestCase
{
    private MockInterface&CredentialsInterface $credentialProvider;
    private MockInterface&SignatureV4 $signer;
    private MockInterface&DbAdapter $database;
    private MockInterface&SqsClient $sqsClient;
    private MockInterface&ClientInterface $httpClient;
    private MockInterface&LoggerInterface $logger;

    private PingHandler $handler;

    public function setUp(): void
    {
        $this->credentialProvider = Mockery::mock(CredentialsInterface::class);
        $this->signer             = Mockery::mock(SignatureV4::class);
        $this->database           = Mockery::mock(DbAdapter::class);
        $this->sqsClient          = Mockery::mock(SqsClient::class);
        $this->httpClient         = Mockery::mock(ClientInterface::class);
        $this->logger             = Mockery::mock(LoggerInterface::class);

        $this->handler = new PingHandler(
            $this->credentialProvider,
            $this->signer,
            $this->database,
            $this->sqsClient,
            'http://test',
            'http://test',
            $this->httpClient,
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

        // good response from Sirius
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->allows('getStatusCode')->andReturns(200);

        $mockRequest = Mockery::mock(Request::class);
        $this->signer->allows('signRequest')->andReturns($mockRequest);

        $this->httpClient->allows('sendRequest')
            ->andReturns($mockResponse);

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
            'gateway' => [
                'ok' => true,
            ],
            'ok' => true,
            'status' => Constants::STATUS_PASS,
            'queue' => [
                'details' => [
                    'available' => true,
                    'length' => 3,
                    'lengthAcceptable' => true,
                ],
                'ok' => true,
            ],
        ];

        $this->logger->expects('info')
            ->with('PingController results', $expectedResult);

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

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->allows('getStatusCode')->andReturns(500);

        $mockRequest = Mockery::mock(Request::class);
        $this->signer->allows('signRequest')->andReturns($mockRequest);

        $this->httpClient->allows('sendRequest')
            ->andReturns($mockResponse);

        $expectedResult = [
            'database' => [
                'ok' => false,
            ],
            'gateway' => [
                'ok' => false,
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
            'queue' => [
                'details' => [
                    'available' => true,
                    'length' => 3,
                    'lengthAcceptable' => true,
                ],
                'ok' => true,
            ],
        ];

        $this->logger->expects('error');

        $this->logger->expects('info')
            ->with('PingController results', $expectedResult);

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }

    public function testItReturnsQueueDownDBandGatewayExceptions(): void
    {
        // bad response from SQS, so we throw an exception
        $this->sqsClient->allows('getQueueAttributes')
            ->andReturns([]);

        // exception from Sirius gateway client
        $this->httpClient->allows('sendRequest')
            ->andThrow(new Exception('something went wrong with the client'));

        // ping response JSON we expect
        $expectedResult = [
            'database' => [
                'ok' => false,
            ],
            'gateway' => [
                'ok' => false,
            ],
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
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
            ->times(3);

        $this->logger->expects('info')
            ->with('PingController results', $expectedResult);

        $result = $this->handler->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }
}
