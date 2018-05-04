<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\DynamoCronLock;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;

class DynamoCronLockTest extends MockeryTestCase
{
    /**
     * @var DynamoCronLock
     */
    private $service;

    /**
     * @var MockInterface|DynamoDbClient
     */
    private $client;

    /**
     * @var MockInterface|Logger
     */
    private $logger;

    protected function setUp()
    {
        $this->client = Mockery::mock(DynamoDbClient::class);

        $this->logger = Mockery::mock(Logger::class);

        $this->service = new DynamoCronLock($this->client, 'unit-test-table', 'unit-test-key-prefix');

        $this->service->setLogger($this->logger);
    }

    public function testGetLockDynamoDbExceptionConditionalCheckFailedException()
    {
        $exception = Mockery::mock(DynamoDbException::class);
        $exception->shouldReceive('getAwsErrorCode')->once()->andReturn('ConditionalCheckFailedException');

        $this->client->shouldReceive('updateItem')->withArgs(function ($args) {
            return $args['TableName'] === 'unit-test-table'
                && $args['Key'] === ['id' => ['S' => 'unit-test-key-prefix/UnitTest']]
                && $args['ExpressionAttributeNames'] === ['#updated' => 'updated']
                && (float)$args['ExpressionAttributeValues'][':updated']['N']
                    > (round(microtime(true) * 1000) - 1000)
                && $args['ExpressionAttributeValues'][':diff']['N']
                    > (round(microtime(true) * 1000) - ((60 * 60) * 1000) - 1000)
                && $args['ConditionExpression'] === '#updated < :diff or attribute_not_exists(#updated)'
                && $args['UpdateExpression'] === 'SET #updated=:updated'
                && $args['ReturnValues'] === 'NONE'
                && $args['ReturnConsumedCapacity'] === 'NONE';
        })->once()
        ->andThrow($exception);

        $result = $this->service->getLock('UnitTest', (60 * 60));

        $this->assertEquals(false, $result);
    }

    public function testGetLockDynamoDbExceptionNotConditionalCheckFailedException()
    {
        $exception = Mockery::mock(DynamoDbException::class);
        $exception->shouldReceive('getAwsErrorCode')->once()->andReturn('UnitTest');

        $this->client->shouldReceive('updateItem')->withArgs(function ($args) {
            return $args['TableName'] === 'unit-test-table'
                && $args['Key'] === ['id' => ['S' => 'unit-test-key-prefix/UnitTest']]
                && $args['ExpressionAttributeNames'] === ['#updated' => 'updated']
                && (float)$args['ExpressionAttributeValues'][':updated']['N']
                    > (round(microtime(true) * 1000) - 1000)
                && $args['ExpressionAttributeValues'][':diff']['N']
                    > (round(microtime(true) * 1000) - ((60 * 60) * 1000) - 1000)
                && $args['ConditionExpression'] === '#updated < :diff or attribute_not_exists(#updated)'
                && $args['UpdateExpression'] === 'SET #updated=:updated'
                && $args['ReturnValues'] === 'NONE'
                && $args['ReturnConsumedCapacity'] === 'NONE';
        })->once()
        ->andThrow($exception);

        $this->logger->shouldReceive('alert')->withArgs([
            'Unexpected exception thrown whilst trying to secure a Dynamo Cron Lock',
            ['exception' => '']])->once();

        $result = $this->service->getLock('UnitTest', (60 * 60));

        $this->assertEquals(false, $result);
    }

    public function testGetLock()
    {
        $this->client->shouldReceive('updateItem')->withArgs(function ($args) {
            return $args['TableName'] === 'unit-test-table'
                && $args['Key'] === ['id' => ['S' => 'unit-test-key-prefix/UnitTest']]
                && $args['ExpressionAttributeNames'] === ['#updated' => 'updated']
                && (float)$args['ExpressionAttributeValues'][':updated']['N']
                    > (round(microtime(true) * 1000) - 1000)
                && $args['ExpressionAttributeValues'][':diff']['N']
                    > (round(microtime(true) * 1000) - ((60 * 60) * 1000) - 1000)
                && $args['ConditionExpression'] === '#updated < :diff or attribute_not_exists(#updated)'
                && $args['UpdateExpression'] === 'SET #updated=:updated'
                && $args['ReturnValues'] === 'NONE'
                && $args['ReturnConsumedCapacity'] === 'NONE';
        })->once();

        $result = $this->service->getLock('UnitTest', (60 * 60));

        $this->assertEquals(true, $result);
    }
}
