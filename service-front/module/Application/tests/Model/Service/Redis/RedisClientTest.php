<?php

namespace ApplicationTest\Model\Service\Redis;

use Application\Model\Service\Redis\RedisClient;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Redis;
use RedisException;
use ReflectionProperty;

class RedisClientTest extends MockeryTestCase
{
    // returns the mock for setting expectations
    private function makeRedisClientWithMock(string $redisUrl): Redis
    {
        $redisMock = Mockery::Mock(Redis::class);
        $this->redisHandler = new RedisClient($redisUrl, 1000, $redisMock);
        return $redisMock;
    }

    public function tearDown(): void
    {
        Mockery::close();
        $this->redisHandler = null;
    }

    public function testConstructorErrorInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new RedisClient('foohost', 100);
    }

    public function testConstructorInstantiatesRedis(): void
    {
        // use reflection to check that the save handler has a Redis
        // instance instantiated where one is not explicitly passed to
        // the constructor
        $redisClientProperty = new ReflectionProperty(RedisClient::class, 'redisClient');
        $redisClientProperty->setAccessible(true);

        $redisHandler = new RedisClient('tcp://localhost', 100);

        $this->assertInstanceOf(Redis::class, $redisClientProperty->getValue($redisHandler));
    }

    public function testConstructorIncludesTls(): void
    {
        // use reflection to check that the host extracted for a TLS
        // hostname includes the tls scheme
        $redisHostProperty = new ReflectionProperty(RedisClient::class, 'redisHost');
        $redisHostProperty->setAccessible(true);

        $redisHandler = new RedisClient('tls://localhost', 100);

        $this->assertSame('tls://localhost', $redisHostProperty->getValue($redisHandler));
    }

    public function testOpenException(): void
    {
        $expected = false;

        $redisMock = $this->makeRedisClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andThrow(new RedisException());

        $actual = $this->redisHandler->open();

        $this->assertSame($expected, $actual);
    }

    public function testOpenSuccess(): void
    {
        $expected = true;

        $redisMock = $this->makeRedisClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(true);

        $actual = $this->redisHandler->open();

        $this->assertSame($expected, $actual);
    }

    public function testWriteError(): void
    {
        $expected = false;

        $redisMock = $this->makeRedisClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with('foo', 1, 'bar')
            ->andReturn(false);

        $actual = $this->redisHandler->write('foo', 'bar');

        $this->assertSame($expected, $actual);
    }

    public function testWriteSuccess(): void
    {
        $redisMock = $this->makeRedisClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with('foo', 1, 'bar')
            ->andReturn(true);

        $actual = $this->redisHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testGetError(): void
    {
        $expected = '';

        $redisMock = $this->makeRedisClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('get')
            ->with('baz')
            ->andReturn(false);

        $actual = $this->redisHandler->read('baz');

        $this->assertSame($expected, $actual);
    }

    public function testGetSuccess(): void
    {
        $expected = 'bar';

        $redisMock = $this->makeRedisClientWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('get')
            ->with('baf')
            ->andReturn($expected);

        $actual = $this->redisHandler->read('baf');

        $this->assertSame($expected, $actual);
    }

    public function testClose()
    {
        $redisMock = $this->makeRedisClientWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('close')
            ->andReturn(true);
        $this->assertSame(true, $this->redisHandler->close());
    }

    public function testDestroy()
    {
        $redisMock = $this->makeRedisClientWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('del')
            ->with('boo')
            ->andReturn(true);
        $this->assertSame(true, $this->redisHandler->destroy('boo'));
    }
}
