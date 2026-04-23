<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\Redis\RedisClient;
use Application\Service\Factory\RedisClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RedisClientFactoryTest extends TestCase
{
    public function testFactoryReturnsRedisClient(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is not available in this environment.');
        }

        $config = [
            'redis' => [
                'url'   => 'redis://localhost:6379',
                'ttlMs' => 3600000,
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $client = (new RedisClientFactory())($container);

        $this->assertInstanceOf(RedisClient::class, $client);
    }
}
