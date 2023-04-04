<?php

namespace ApplicationTest\Controller;

use Application\Controller\PingController;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Aws\Sqs\SqsClient;
use MakeShared\Constants;
use MakeShared\Logging\Logger;
use MakeShared\Logging\SimpleLogger;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\View\Model\JsonModel;
use Http\Client\HttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class PingControllerTest extends MockeryTestCase
{
    /**
     * @var PingController
     */
    private $controller;

    /**
     * @var CredentialProvider
     */
    private $credentialProvider;

    /**
     * @var SignatureV4
     */
    private $signer;

    /**
     * @var DbAdapter|MockInterface
     */
    private $database;

    /**
     * @var SqsClient|MockInterface
     */
    private $sqsClient;

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp(): void
    {
        $this->database = Mockery::mock(DbAdapter::class);

        $this->sqsClient = Mockery::mock(SqsClient::class);

        $this->httpClient = Mockery::mock(HttpClient::class);

        $this->credentialProvider = Mockery::mock(CredentialsInterface::class);

        $this->signer = Mockery::mock(SignatureV4::class);

        $this->controller = new PingController(
            $this->credentialProvider,
            $this->signer,
            $this->database,
            $this->sqsClient,
            'http://test',
            'http://test',
            $this->httpClient
        );

        $this->logger = Mockery::mock(Logger::class);
        $this->controller->setLogger($this->logger);
    }

    public function testIndexActionSuccessDbAndGatewayDown()
    {
        $this->sqsClient->shouldReceive('getQueueAttributes')
            ->andReturn([
                'Attributes' => [
                    'ApproximateNumberOfMessages' => 1,
                    'ApproximateNumberOfMessagesNotVisible' => 2,
                ]
            ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(500);

        $mockRequest = Mockery::mock(Request::class);
        $this->signer->shouldReceive('signRequest')->andReturn($mockRequest);

        $this->httpClient->shouldReceive('sendRequest')
            ->andReturn($mockResponse);

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

        $this->logger->shouldReceive('err')
            ->once();

        $this->logger->shouldReceive('info')
            ->with('PingController results', $expectedResult)
            ->once();

        /** @var JsonModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($expectedResult, $result->getVariables());
    }

    public function testIndexActionSuccessQueueDownDbAndGatewayExceptions()
    {
        $this->controller->setLogger(new SimpleLogger());

        // bad response from SQS, so we throw an exception
        $this->sqsClient->shouldReceive('getQueueAttributes')
            ->andReturn([]);

        // exception from Sirius gateway client
        $this->httpClient->shouldReceive('sendRequest')
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

        /** @var JsonModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($expectedResult, $result->getVariables());
    }

    public function testIndexActionSuccessAllServicesOk()
    {
        $this->controller->setLogger(new SimpleLogger());

        // good response from SQS
        $this->sqsClient->shouldReceive('getQueueAttributes')
            ->andReturn([
                'Attributes' => [
                    'ApproximateNumberOfMessages' => 1,
                    'ApproximateNumberOfMessagesNotVisible' => 2,
                ]
            ]);

        // good response from Sirius
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(200);

        $mockRequest = Mockery::mock(Request::class);
        $this->signer->shouldReceive('signRequest')->andReturn($mockRequest);

        $this->httpClient->shouldReceive('sendRequest')
            ->andReturn($mockResponse);

        // good response from db
        $mockConnection = Mockery::mock(ConnectionInterface::class);
        $mockConnection->shouldReceive('connect')->andReturn($mockConnection);

        $mockDriver = Mockery::mock(DriverInterface::class);
        $mockDriver->shouldReceive('getConnection')->andReturn($mockConnection);

        $this->database->shouldReceive('getDriver')->andReturn($mockDriver);

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

        /** @var JsonModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($expectedResult, $result->getVariables());
    }

    public function testElbAction()
    {
        $response = $this->controller->elbAction();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Happy face', $response->getContent());
    }
}
