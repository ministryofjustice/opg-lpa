<?php

declare(strict_types=1);

namespace AppTest\Service\Redis;

use App\Service\Redis\RedisClient;
use App\Service\Redis\RedisClientFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class RedisClientFactoryTest extends TestCase
{
    private function containerWithRedisConfig(?array $redisConfig): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            ['config', $redisConfig === null ? [] : ['redis' => $redisConfig]],
            [LoggerInterface::class, $this->createMock(LoggerInterface::class)],
        ]);

        return $container;
    }

    public function testCreatesRedisClient(): void
    {
        $client = new RedisClientFactory()($this->containerWithRedisConfig([
            'url'           => 'tcp://127.0.0.1:1',
            'keyTtlSeconds' => 10800,
            'authToken'     => 'secret-token',
        ]));

        $this->assertInstanceOf(RedisClient::class, $client);
    }

    public static function invalidConfigProvider(): array
    {
        return [
            'no redis config'    => [null, 'Redis configuration is not available'],
            'missing url'        => [['keyTtlSeconds' => 1, 'authToken' => 'x'], 'Redis URL is not configured'],
            'missing ttl'        => [['url' => 'tcp://redis', 'authToken' => 'x'], 'Redis key TTL is not configured'],
            'missing auth token' => [['url' => 'tcp://redis', 'keyTtlSeconds' => 1], 'Redis auth token is not configured'],
            'unparseable host'   => [
                ['url' => 'not-a-url', 'keyTtlSeconds' => 1, 'authToken' => 'x'],
                'Redis hostname could not be parsed from provided URL',
            ],
        ];
    }

    #[DataProvider('invalidConfigProvider')]
    public function testThrowsOnInvalidConfig(?array $redisConfig, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new RedisClientFactory())($this->containerWithRedisConfig($redisConfig));
    }
}
