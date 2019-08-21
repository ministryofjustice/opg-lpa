<?php

namespace ApplicationTest\Model\Service\Session\SaveHandler;

use Application\Model\Service\Session\SaveHandler\HashedKeyDynamoDbSessionConnection;
use Aws\CommandInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\ResultInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class HashedKeyDynamoDbSessionConnectionTest extends MockeryTestCase
{
    /**
     * @var $client DynamoDbClient|MockInterface
     */
    private $client;

    /**
     * @var $service HashedKeyDynamoDbSessionConnection
     */
    private $service;

    private $errors;

    public function setUp() : void
    {
        $this->client = Mockery::mock(DynamoDbClient::class);

        $this->service = new HashedKeyDynamoDbSessionConnection($this->client);
    }

    public function testRead() : void
    {
        $this->client->shouldReceive('getItem')
            ->withArgs([[
                'TableName' => 'sessions',
                'Key' => [
                    'id' => [
                        'S' => 'f16654bcb45d7312842469755a7f7024e3568f9af64bfe8f453343f8be54df0075bfd86f26706f19888a3' .
                            'c32673a7aa900e2e4b5a92141dd4148c08dd554f8ee'
                    ]
                ],
                'ConsistentRead' => true
            ]])->once()
            ->andReturn(['Item' => [
                'Some' => ['Value', 'Another'],
                'Thing' => [1, 2]
            ]]);

        $result = $this->service->read('test-id');

        $this->assertEquals([
            'Some' => 'Value',
            'Thing' => 1
        ], $result);
    }

    public function testReadDynamoDbException() : void
    {
        /** @var $commandInterface CommandInterface */
        $commandInterface = Mockery::mock(CommandInterface::class);

        $this->client->shouldReceive('getItem')
            ->withArgs([[
                'TableName' => 'sessions',
                'Key' => [
                    'id' => [
                        'S' => 'f16654bcb45d7312842469755a7f7024e3568f9af64bfe8f453343f8be54df0075bfd86f26706f19888a3' .
                            'c32673a7aa900e2e4b5a92141dd4148c08dd554f8ee'
                    ]
                ],
                'ConsistentRead' => true
            ]])->once()
            ->andThrow(new DynamoDbException('Test error', $commandInterface));

        $result = $this->service->read('test-id');

        $this->assertEquals([], $result);
    }

    public function testWrite() : void
    {
        // WithArgs omitted as the use of time() makes it unreliable
        $this->client->shouldReceive('updateItem')
            ->once()
            ->andReturn(true);

        $result = $this->service->write('test-id', 'test data', true);

        $this->assertTrue($result);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $this->errors[] = compact("errno", "errstr", "errfile", "errline", "errcontext");
    }

    public function testWriteDynamoDbException() : void
    {
        set_error_handler(array($this, "errorHandler"));

        /** @var $commandInterface CommandInterface */
        $commandInterface = Mockery::mock(CommandInterface::class);

        $this->client->shouldReceive('updateItem')
            ->once()
            ->andThrow(new DynamoDbException('Test error', $commandInterface));

        $result = $this->service->write('test-id', 'test data', true);

        $this->assertEquals('Error writing session test-id: Test error', $this->errors[0]['errstr']);

        $this->assertEquals(false, $result);
    }

    public function testDelete() : void
    {
        $this->client->shouldReceive('deleteItem')
            ->withArgs([[
                'TableName' => 'sessions',
                'Key' => [
                    'id' => [
                        'S' => 'f16654bcb45d7312842469755a7f7024e3568f9af64bfe8f453343f8be54df0075bfd86f26706f19888a3' .
                            'c32673a7aa900e2e4b5a92141dd4148c08dd554f8ee'
                    ]
                ]
            ]])->once()
            ->andReturn(true);

        $result = $this->service->delete('test-id');

        $this->assertTrue($result);
    }

    public function testDeleteDynamoDbException() : void
    {
        set_error_handler(array($this, "errorHandler"));

        /** @var $commandInterface CommandInterface */
        $commandInterface = Mockery::mock(CommandInterface::class);

        $this->client->shouldReceive('deleteItem')
            ->withArgs([[
                'TableName' => 'sessions',
                'Key' => [
                    'id' => [
                        'S' => 'f16654bcb45d7312842469755a7f7024e3568f9af64bfe8f453343f8be54df0075bfd86f26706f19888a3' .
                            'c32673a7aa900e2e4b5a92141dd4148c08dd554f8ee'
                    ]
                ]
            ]])->once()
            ->andThrow(new DynamoDbException('Test error', $commandInterface));

        $result = $this->service->delete('test-id');

        $this->assertEquals('Error deleting session test-id: Test error', $this->errors[0]['errstr']);

        $this->assertFalse($result);
    }

    public function testDeleteExpired() : void
    {
        $paginator = Mockery::mock();
        $paginator->shouldReceive('search')
            ->withArgs(['Items'])
            ->andReturn([['id' => 'value 1'], ['id' => 'value 2']]);

        $command = Mockery::mock(CommandInterface::class);

        $this->client->shouldReceive('getCommand')
            ->withArgs(['BatchWriteItem',
                ['RequestItems' =>
                    ['sessions' =>
                        [
                            ['DeleteRequest' =>
                                ['Key' =>
                                    ['id' => 'value 1']
                                ]
                            ],
                            ['DeleteRequest' =>
                                ['Key' =>
                                    ['id' => 'value 2']
                                ]
                            ]
                        ]
                    ]
                ]
            ])
            //->once()
            ->andReturn($command);

        $resultInterface = Mockery::mock(ResultInterface::class);
        $resultInterface->shouldReceive('hasKey')->andReturn(true);
        $resultInterface->shouldReceive('offsetGet')->andReturn([]);

        $this->client->shouldReceive('executeAsync')->once()->andReturn($resultInterface);

        // WithArgs omitted as the use of time() makes it unreliable
        $this->client->shouldReceive('getPaginator')->once()->andReturn($paginator);

        $this->service->deleteExpired();
    }
}
