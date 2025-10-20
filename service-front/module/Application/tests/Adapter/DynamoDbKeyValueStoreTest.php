<?php

declare(strict_types=1);

namespace ApplicationTest\Adapter;

use Application\Adapter\DynamoDbKeyValueStore;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class DynamoDbKeyValueStoreTest extends MockeryTestCase
{
    private DynamoDbKeyValueStore $dynamoDbKeyValueStore;
    private MockInterface|DynamoDbClient $dynamoDbClient;

    public function setUp(): void
    {
        $config['settings']['table_name'] = 'test_table';
        $config['keyPrefix'] = 'key_prefix';

        $this->dynamoDbClient = Mockery::mock(DynamoDbClient::class);

        $this->dynamoDbKeyValueStore = new DynamoDbKeyValueStore($config);
        $this->dynamoDbKeyValueStore->setDynamoDbClient($this->dynamoDbClient);
    }

    public function testSetItemPopulatedValue(): void
    {
        $this->dynamoDbClient->expects('putItem')->withArgs([[
            'TableName' => 'test_table',
            'Item' => [
                'id'    => ['S' => 'key_prefix/test key'],
                'value' => ['B' => 'test value'],
            ]]])->once();

        $this->dynamoDbKeyValueStore->setItem('test key', 'test value');
    }

    public function testSetItemEmptyValue(): void
    {
        $this->dynamoDbClient->expects('putItem')->withArgs([[
            'TableName' => 'test_table',
            'Item' => [
                'id'    => ['S' => 'key_prefix/test key'],
                'value' => ['NULL' => true],
            ]]])->once();

        $this->dynamoDbKeyValueStore->setItem('test key', '');
    }

    public function testRemoveItem(): void
    {
        $this->dynamoDbClient->expects('deleteItem')->withArgs([[
            'TableName' => 'test_table',
            'Key' => [
                'id' => ['S' => 'key_prefix/test key']
            ]]])->once();

        $this->dynamoDbKeyValueStore->removeItem('test key');
    }

    public function testGetItemSuccess(): void
    {
        $returnedItem['Item']['value']['B'] = 'test token';

        $this->dynamoDbClient->expects('getItem')->withArgs([[
            'TableName' => 'test_table',
            'Key' => [
                'id' => ['S' => 'key_prefix/test key']]
            ]])->andReturn($returnedItem)->once();

        $success = false;
        $casToken = 'unmodified token';
        $result = $this->dynamoDbKeyValueStore->getItem('test key', $success, $casToken);

        $this->assertNotNull($result);
        $this->assertEquals('test token', $result);
        $this->assertEquals(true, $success);
        $this->assertEquals('unmodified token', $casToken);
    }

    public function testGetItemFailed(): void
    {
        $this->dynamoDbClient->expects('getItem')->andThrow(Exception::class)->once();

        $success = true;
        $casToken = 'unmodified token';
        $result = $this->dynamoDbKeyValueStore->getItem('test key', $success, $casToken);

        $this->assertNull($result);
        $this->assertEquals(false, $success);
        $this->assertEquals('unmodified token', $casToken);
    }
}
