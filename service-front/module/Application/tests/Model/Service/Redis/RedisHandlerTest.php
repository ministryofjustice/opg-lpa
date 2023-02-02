<?php

namespace ApplicationTest\Model\Service\Redis;

use Application\Model\Service\Redis\RedisHandler;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Redis;
use RedisException;
use ReflectionProperty;

class RedisHandlerTest extends MockeryTestCase
{
    // returns the mock for setting expectations
    private function makeRedisHandlerWithMock(string $redisUrl): Redis
    {
        $redisMock = Mockery::Mock(Redis::class);
        $this->redisHandler = new RedisHandler($redisUrl, 1000, $redisMock);
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
        $client = new RedisHandler('foohost', 100);
    }

    public function testConstructorInstantiatesRedis(): void
    {
        // use reflection to check that the save handler has a Redis
        // instance instantiated where one is not explicitly passed to
        // the constructor
        $redisClientProperty = new ReflectionProperty(RedisHandler::class, 'redisClient');
        $redisClientProperty->setAccessible(true);

        $redisHandler = new RedisHandler('tcp://localhost', 100);

        $this->assertInstanceOf(Redis::class, $redisClientProperty->getValue($redisHandler));
    }

    public function testConstructorIncludesTls(): void
    {
        // use reflection to check that the host extracted for a TLS
        // hostname includes the tls scheme
        $redisHostProperty = new ReflectionProperty(RedisHandler::class, 'redisHost');
        $redisHostProperty->setAccessible(true);

        $redisHandler = new RedisHandler('tls://localhost', 100);

        $this->assertSame('tls://localhost', $redisHostProperty->getValue($redisHandler));
    }

    public function testOpenException(): void
    {
        $expected = false;

        $redisMock = $this->makeRedisHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andThrow(new RedisException());

        $actual = $this->redisHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testOpenSuccess(): void
    {
        $expected = true;

        $redisMock = $this->makeRedisHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(true);

        $actual = $this->redisHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testWriteError(): void
    {
        $expected = false;

        $redisMock = $this->makeRedisHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(RedisHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(false);

        $actual = $this->redisHandler->write('foo', 'bar');

        $this->assertSame($expected, $actual);
    }

    public function testWriteSuccess(): void
    {
        $redisMock = $this->makeRedisHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(RedisHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(true);

        $actual = $this->redisHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testGetError(): void
    {
        $expected = '';

        $redisMock = $this->makeRedisHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('get')
            ->with(RedisHandler::SESSION_PREFIX . 'baz')
            ->andReturn(false);

        $actual = $this->redisHandler->read('baz');

        $this->assertSame($expected, $actual);
    }

    public function testGetSuccess(): void
    {
        $expected = 'bar';

        $redisMock = $this->makeRedisHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('get')
            ->with(RedisHandler::SESSION_PREFIX . 'baf')
            ->andReturn($expected);

        $actual = $this->redisHandler->read('baf');

        $this->assertSame($expected, $actual);
    }

    public function testClose()
    {
        $redisMock = $this->makeRedisHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('close')
            ->andReturn(true);
        $this->assertSame(true, $this->redisHandler->close());
    }

    public function testDestroy()
    {
        $redisMock = $this->makeRedisHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('del')
            ->with(RedisHandler::SESSION_PREFIX . 'boo')
            ->andReturn(true);
        $this->assertSame(true, $this->redisHandler->destroy('boo'));
    }

    public function testGc()
    {
        $redisMock = $this->makeRedisHandlerWithMock('tcp://barhost:6737');
        $this->assertSame(1, $this->redisHandler->gc(1));
    }
}
