<?php

namespace ApplicationTest\Controller;

use Application\Controller\PingController;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
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
     * @var DynamoQueueClient|MockInterface
     */
    private $queueClient;

    /**
     * @var ZendDbAdapter|MockInterface
     */
    private $database;

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp()
    {
        $this->queueClient = Mockery::mock(DynamoQueueClient::class);

        $this->database = Mockery::mock(ZendDbAdapter::class);

        $this->controller = new PingController($this->queueClient, $this->database);

        $this->logger = Mockery::mock(Logger::class);
        $this->controller->setLogger($this->logger);
    }

    public function testIndexActionSuccess()
    {
        $this->queueClient->shouldReceive('countWaitingJobs')
            ->andReturn(12);

        $pingResult = [
            'database' => [
                'ok' => false,
            ],
            'ok' => false,
            'queue' => [
                'details' => [
                    'available' => true,
                    'length' => 12,
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
