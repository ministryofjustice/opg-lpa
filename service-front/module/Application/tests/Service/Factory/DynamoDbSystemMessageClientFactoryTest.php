<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\DynamoDbSystemMessageClientFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DynamoDbSystemMessageClientFactoryTest extends TestCase
{
    public function testFactoryReturnsDynamoDbClient(): void
    {
        $config = [
            'admin' => [
                'dynamodb' => [
                    'client' => [
                        'region'  => 'eu-west-1',
                        'version' => 'latest',
                    ],
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $client = (new DynamoDbSystemMessageClientFactory())($container);

        $this->assertInstanceOf(DynamoDbClient::class, $client);
    }
}
