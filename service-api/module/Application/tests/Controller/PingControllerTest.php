<?php

namespace ApplicationTest\Controller;

use Application\Controller\PingController;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Aws\Sqs\SqsClient;
use MakeShared\Logging\Logger;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
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
     * @var ZendDbAdapter|MockInterface
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
        $this->database = Mockery::mock(ZendDbAdapter::class);

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

    public function testIndexActionSuccess()
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

        $pingResult = [
            'database' => [
                'ok' => false,
            ],
            'gateway' => [
                'ok' => false,
            ],
            'ok' => false,
            'queue' => [
                'details' => [
                    'available' => true,
                    'length' => 3,
                    'lengthAcceptable' => true,
                ],
                'ok' => true,
            ],
        ];

        $this->logger->shouldReceive('info')
            ->with('PingController results', $pingResult)
            ->once();

        /** @var JsonModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($pingResult, $result->getVariables());
    }
}
