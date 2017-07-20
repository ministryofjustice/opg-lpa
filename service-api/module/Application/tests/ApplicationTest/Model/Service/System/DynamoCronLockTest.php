<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\DynamoCronLock;
use Aws\Command;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Mockery;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DynamoCronLockTest extends \PHPUnit_Framework_TestCase
{
    private $config = array();

    public function setUp()
    {
        parent::setUp();

        $this->config = [
            'cron' => [
                'lock' => [
                    'dynamodb' => [
                        'client' => [
                            'version' => '2012-08-10',
                            'region' => 'eu-west-1',
                            'credentials' => null
                        ],
                        'settings' => [
                            'table_name' => 'lpa-locks-shared'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testConstructor()
    {
        new DynamoCronLock($this->config['cron']['lock']['dynamodb']);
    }

    public function testGetLock()
    {
        $dynamoCronLock = new TestableDynamoCronLock($this->config['cron']['lock']['dynamodb']);
        $dynamoCronLock->mockClient = Mockery::mock(DynamoDbClient::class);
        $dynamoCronLock->mockClient->shouldReceive('updateItem')->once();

        $lock = $dynamoCronLock->getLock('test', 1);

        $this->assertTrue($lock);
    }

    public function testGetLockException()
    {
        $dynamoCronLock = new TestableDynamoCronLock($this->config['cron']['lock']['dynamodb']);
        $dynamoCronLock->mockClient = Mockery::mock(DynamoDbClient::class);
        $dynamoCronLock->mockClient->shouldReceive('updateItem')->andThrow(new DynamoDbException('Test', new Command('Test')))->once();

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('alert')->once();

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);
        $dynamoCronLock->setServiceLocator($serviceLocatorMock);

        $lock = $dynamoCronLock->getLock('test', 1);

        $this->assertFalse($lock);
    }

    public function testGetLockExpectedException()
    {
        $dynamoCronLock = new TestableDynamoCronLock($this->config['cron']['lock']['dynamodb']);
        $dynamoCronLock->mockClient = Mockery::mock(DynamoDbClient::class);
        $dynamoCronLock->mockClient->shouldReceive('updateItem')->andThrow(new DynamoDbException('Test', new Command('Test'), ['code' => 'ConditionalCheckFailedException']))->once();

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('alert')->once();

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);
        $dynamoCronLock->setServiceLocator($serviceLocatorMock);

        $lock = $dynamoCronLock->getLock('test', 1);

        $this->assertFalse($lock);
    }
}