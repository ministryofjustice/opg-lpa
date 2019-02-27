<?php

namespace ApplicationTest\Adapter;

use Application\Adapter\DynamoDbKeyValueStore;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;

class DynamoDbKeyValueStoreTest extends MockeryTestCase
{
    /**
     * @var DynamoDbKeyValueStore
     */
    private $dynamoDbKeyValueStore;

    /**
     * @var MockInterface|DynamoDbClient
     */
    private $dynamoDbClient;

    public function setUp()
    {
        $config['settings']['table_name'] = 'test_table';
        $config['keyPrefix'] = 'key_prefix';

        $this->dynamoDbClient = Mockery::mock(DynamoDbClient::class);

        $this->dynamoDbKeyValueStore = new DynamoDbKeyValueStore($config);
        $this->dynamoDbKeyValueStore->setDynamoDbClient($this->dynamoDbClient);
    }

    public function testSetItemPopulatedValue()
    {
        $this->dynamoDbClient->expects('putItem')->withArgs([[
            'TableName' => 'test_table',
            'Item' => [
                'id'    => ['S' => 'key_prefix/test key'],
                'value' => ['B' => 'test value'],
            ]]])->once();

        $this->dynamoDbKeyValueStore->setItem('test key', 'test value');
    }

    public function testSetItemEmptyValue()
    {
        $this->dynamoDbClient->expects('putItem')->withArgs([[
            'TableName' => 'test_table',
            'Item' => [
                'id'    => ['S' => 'key_prefix/test key'],
                'value' => ['NULL' => true],
            ]]])->once();

        $this->dynamoDbKeyValueStore->setItem('test key', '');
    }

    public function testRemoveItem()
    {
        $this->dynamoDbClient->expects('deleteItem')->withArgs([[
            'TableName' => 'test_table',
            'Key' => [
                'id' => ['S' => 'key_prefix/test key']
            ]]])->once();

        $this->dynamoDbKeyValueStore->removeItem('test key');
    }

    public function testGetItemSuccess()
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

    public function testGetItemFailed()
    {
        $this->dynamoDbClient->expects('getItem')->andThrow(Exception::class)->once();

        $success = true;
        $casToken = 'unmodified token';
        $result = $this->dynamoDbKeyValueStore->getItem('test key', $success, $casToken);

        $this->assertNull($result);
        $this->assertEquals(false, $success);
        $this->assertEquals('unmodified token', $casToken);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The addItem method has not been implemented.
     */
    public function testAddItem()
    {
        $this->dynamoDbKeyValueStore->addItem('test key', 'test value');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The addItems method has not been implemented.
     */
    public function testAddItems()
    {
        $this->dynamoDbKeyValueStore->addItems(['test items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The checkAndSetItem method has not been implemented.
     */
    public function testCheckAndSetItem()
    {
        $this->dynamoDbKeyValueStore->checkAndSetItem('test token', 'test key', 'test value');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The decrementItem method has not been implemented.
     */
    public function testDecrementItem()
    {
        $this->dynamoDbKeyValueStore->decrementItem('test key', 'test value');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The decrementItems method has not been implemented.
     */
    public function testDecrementItems()
    {
        $this->dynamoDbKeyValueStore->decrementItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The getCapabilities method has not been implemented.
     */
    public function testGetCapabilities()
    {
        $this->dynamoDbKeyValueStore->getCapabilities();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The getItems method has not been implemented.
     */
    public function testGetItems()
    {
        $this->dynamoDbKeyValueStore->getItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The getMetadata method has not been implemented.
     */
    public function testGetMetadata()
    {
        $this->dynamoDbKeyValueStore->getMetadata('items');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The getMetadatas method has not been implemented.
     */
    public function testGetMetadatas()
    {
        $this->dynamoDbKeyValueStore->getMetadatas(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The getOptions method has not been implemented.
     */
    public function testGetOptions()
    {
        $this->dynamoDbKeyValueStore->getOptions();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The hasItem method has not been implemented.
     */
    public function testHasItem()
    {
        $this->dynamoDbKeyValueStore->hasItem('items');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The hasItems method has not been implemented.
     */
    public function testHasItems()
    {
        $this->dynamoDbKeyValueStore->hasItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The incrementItem method has not been implemented.
     */
    public function testIncrementItem()
    {
        $this->dynamoDbKeyValueStore->incrementItem('test key', 'test value');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The incrementItems method has not been implemented.
     */
    public function testIncrementItems()
    {
        $this->dynamoDbKeyValueStore->incrementItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The removeItems method has not been implemented.
     */
    public function testRemoveItems()
    {
        $this->dynamoDbKeyValueStore->removeItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The replaceItem method has not been implemented.
     */
    public function testReplaceItem()
    {
        $this->dynamoDbKeyValueStore->replaceItem('test key', 'test value');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The replaceItems method has not been implemented.
     */
    public function testReplaceItems()
    {
        $this->dynamoDbKeyValueStore->replaceItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The setItems method has not been implemented.
     */
    public function testSetItems()
    {
        $this->dynamoDbKeyValueStore->setItems(['items']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The setOptions method has not been implemented.
     */
    public function testSetOptions()
    {
        $this->dynamoDbKeyValueStore->setOptions(['options']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The touchItem method has not been implemented.
     */
    public function testTouchItem()
    {
        $this->dynamoDbKeyValueStore->touchItem('test key');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The touchItems method has not been implemented.
     */
    public function testTouchItems()
    {
        $this->dynamoDbKeyValueStore->touchItems(['key 1', 'key 2']);
    }
}
