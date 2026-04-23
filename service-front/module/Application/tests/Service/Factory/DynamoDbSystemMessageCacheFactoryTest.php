<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\Service\Factory\DynamoDbSystemMessageCacheFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DynamoDbSystemMessageCacheFactoryTest extends TestCase
{
    public function testFactoryReturnsDynamoDbKeyValueStore(): void
    {
        $config = [
            'admin' => [
                'dynamodb' => [
                    'keyPrefix' => '',
                    'settings'  => ['table_name' => 'system-messages'],
                    'region'    => 'eu-west-1',
                ],
            ],
            'stack' => ['name' => 'test-stack'],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'                    => $config,
            'DynamoDbSystemMessageClient' => $this->createMock(DynamoDbClient::class),
        });

        $store = (new DynamoDbSystemMessageCacheFactory())($container);

        $this->assertInstanceOf(DynamoDbKeyValueStore::class, $store);
    }
}
