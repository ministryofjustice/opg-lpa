<?php

namespace ApplicationTest\Controller;

use Application\Controller\PingController;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Aws\Sqs\SqsClient;
use Opg\Lpa\Logger\Logger;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Zend\View\Model\JsonModel;

class PingControllerTest extends MockeryTestCase
{
    /**
     * @var PingController
     */
    private $controller;

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

    public function setUp()
    {
        $this->database = Mockery::mock(ZendDbAdapter::class);

        $this->sqsClient = Mockery::mock(SqsClient::class);

        $this->controller = new PingController($this->database, $this->sqsClient, 'http://test');

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

        $pingResult = [
            'database' => [
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
