<?php

declare(strict_types=1);

namespace AppTest\Service\Session;

use App\Service\Redis\RedisClient;
use App\Service\Session\FilteringSaveHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class FilteringSaveHandlerTest extends TestCase
{
    private RedisClient&MockObject $redisClient;
    private LoggerInterface&MockObject $logger;
    private FilteringSaveHandler $handler;

    protected function setUp(): void
    {
        $this->redisClient = $this->createMock(RedisClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new FilteringSaveHandler($this->redisClient, [], $this->logger);
    }

    public function testOpenDelegatesToRedisClient(): void
    {
        $this->redisClient->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $this->assertTrue($this->handler->open('', 'PHPSESSID'));
    }

    public function testCloseDelegatesToRedisClient(): void
    {
        $this->redisClient->expects($this->once())
            ->method('close')
            ->willReturn(true);

        $this->assertTrue($this->handler->close());
    }

    public function testReadReturnsValueFromRedisUsingPrefixedKey(): void
    {
        $this->redisClient->expects($this->once())
            ->method('read')
            ->with('PHPREDIS_SESSION:abc123')
            ->willReturn('stored-session-data');

        $this->assertSame('stored-session-data', $this->handler->read('abc123'));
    }

    public function testWriteWithoutFiltersWritesToRedis(): void
    {
        $this->redisClient->expects($this->once())
            ->method('write')
            ->with('PHPREDIS_SESSION:abc123', 'session-data')
            ->willReturn(true);

        $this->assertTrue($this->handler->write('abc123', 'session-data'));
    }

    public function testWriteWithFilterReturningFalseDoesNotWriteAndReturnsTrue(): void
    {
        $handler = new FilteringSaveHandler($this->redisClient, [static fn (): bool => false], $this->logger);

        $this->redisClient->expects($this->never())->method('write');

        $this->assertTrue($handler->write('abc123', 'session-data'));
    }

    public function testWriteWithFilterReturningTrueWritesToRedis(): void
    {
        $handler = new FilteringSaveHandler($this->redisClient, [static fn (): bool => true], $this->logger);

        $this->redisClient->expects($this->once())
            ->method('write')
            ->with('PHPREDIS_SESSION:abc123', 'session-data')
            ->willReturn(true);

        $this->assertTrue($handler->write('abc123', 'session-data'));
    }

    public function testDestroyDelegatesToRedisClient(): void
    {
        $this->redisClient->expects($this->once())
            ->method('destroy')
            ->with('PHPREDIS_SESSION:abc123')
            ->willReturn(true);

        $this->assertTrue($this->handler->destroy('abc123'));
    }

    public function testGcReturnsOne(): void
    {
        $this->assertSame(1, $this->handler->gc(3600));
    }

    public function testAddFilterReturnsSelf(): void
    {
        $this->assertSame($this->handler, $this->handler->addFilter(static fn (): bool => true));
    }
}
