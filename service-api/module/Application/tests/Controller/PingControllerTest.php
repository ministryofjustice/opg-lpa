<?php

namespace ApplicationTest\Controller;

use Application\Controller\PingController;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Opg\Lpa\Logger\Logger;
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
     * @var Database|MockInterface
     */
    private $database;

    /**
     * @var Logger|MockInterface
     */
    private $logger;

    public function setUp()
    {
        $this->queueClient = Mockery::mock(DynamoQueueClient::class);

        $this->database = Mockery::mock(Database::class);

        $this->controller = new PingController($this->queueClient, $this->database);

        $this->logger = Mockery::mock(Logger::class);
        $this->controller->setLogger($this->logger);
    }

    public function testIndexActionSuccess()
    {
        $this->queueClient->shouldReceive('countWaitingJobs')
            ->andReturn(12);

        /** @var Manager $manager */
        $manager = Mockery::mock();

        $this->database->shouldReceive('getManager')
            ->andReturn($manager);
        $this->database->shouldReceive('getDatabaseName')
            ->andReturn('database-name');

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
