<?php

declare(strict_types=1);

namespace AppTest\Service\Redis;

use App\Service\Redis\RedisClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;

final class RedisClientTest extends TestCase
{
    private const HOST = 'tls://cache.example.com';
    private const PORT = 6379;
    private const TOKEN = 'secret-token';
    private const TTL = 10800;

    private Redis&MockObject $redis;
    private LoggerInterface&MockObject $logger;
    private RedisClient $client;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(Redis::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new RedisClient(self::TTL, $this->redis, self::HOST, self::PORT, self::TOKEN, $this->logger);
    }

    public function testOpenConnectsThenAuthenticates(): void
    {
        $this->redis->expects($this->once())->method('connect')->with(self::HOST, self::PORT)->willReturn(true);
        $this->redis->expects($this->once())->method('auth')->with(self::TOKEN)->willReturn(true);

        $this->assertTrue($this->client->open());
    }

    public function testOpenReturnsFalseAndSkipsAuthWhenConnectFails(): void
    {
        $this->redis->method('connect')->willReturn(false);
        $this->redis->expects($this->never())->method('auth');

        $this->assertFalse($this->client->open());
    }

    public function testOpenReturnsFalseWhenAuthFails(): void
    {
        $this->redis->method('connect')->willReturn(true);
        $this->redis->method('auth')->willReturn(false);

        $this->assertFalse($this->client->open());
    }

    public function testOpenLogsAndReturnsFalseWhenConnectThrows(): void
    {
        $exception = new RedisException('an error');
        $this->redis->method('connect')->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unable to connect to Redis Server', [
                'exception' => $exception,
                'host'      => self::HOST,
                'port'      => self::PORT,
            ]);

        $this->assertFalse($this->client->open());
    }

    public function testOpenLogsAndReturnsFalseWhenAuthThrows(): void
    {
        $this->redis->method('connect')->willReturn(true);
        $this->redis->method('auth')->willThrowException(new RedisException('some auth error'));
        $this->logger->expects($this->once())->method('error');

        $this->assertFalse($this->client->open());
    }

    public function testCloseDelegates(): void
    {
        $this->redis->expects($this->once())->method('close')->willReturn(true);

        $this->assertTrue($this->client->close());
    }

    public function testReadReturnsStoredData(): void
    {
        $this->redis->expects($this->once())->method('get')->with('session-id')->willReturn('payload');

        $this->assertSame('payload', $this->client->read('session-id'));
    }

    public function testReadReturnsEmptyStringWhenKeyMissing(): void
    {
        $this->redis->method('get')->willReturn(false);

        $this->assertSame('', $this->client->read('missing'));
    }

    public function testWriteStoresWithTtl(): void
    {
        $this->redis->expects($this->once())
            ->method('setEx')
            ->with('session-id', self::TTL, 'payload')
            ->willReturn(true);

        $this->assertTrue($this->client->write('session-id', 'payload'));
    }

    public function testWriteReturnsFalseOnFailure(): void
    {
        $this->redis->method('setEx')->willReturn(false);

        $this->assertFalse($this->client->write('session-id', 'payload'));
    }

    public function testDestroyDeletesKey(): void
    {
        $this->redis->expects($this->once())->method('del')->with('session-id');

        $this->assertTrue($this->client->destroy('session-id'));
    }
}
