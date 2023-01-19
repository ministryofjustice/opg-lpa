<?php

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\FilteringSaveHandler;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Redis;
use RedisException;
use ReflectionProperty;

class FilteringSaveHandlerTest extends MockeryTestCase
{
    /** @var FilteringSaveHandler */
    private $saveHandler;

    // returns the mock for setting expectations
    private function makeSaveHandlerWithMock(string $redisUrl): Redis
    {
        $redisMock = Mockery::Mock(Redis::class);
        $this->saveHandler = new FilteringSaveHandler($redisUrl, 1000, [], $redisMock);
        return $redisMock;
    }

    public function tearDown(): void
    {
        Mockery::close();
        $this->saveHandler = null;
    }

    public function testConstructorErrorInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new FilteringSaveHandler('foohost', 100);
    }

    public function testConstructorWithFiltersCausesIgnore(): void
    {
        $redisMock = Mockery::Mock(Redis::class);

        // redisMock should not receive a call to set the value,
        // as the filters should prevent the write from happening
        $redisMock->shouldNotReceive('setEx')->with(
            FilteringSaveHandler::SESSION_PREFIX . 'foo',
            100,
            'bar'
        )->andReturn(true);

        $filter = function () {
            return false;
        };
        $saveHandler = new FilteringSaveHandler('tcp://localhost', 100, [$filter], $redisMock);
        $actual = $saveHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testConstructorInstantiatesRedis(): void
    {
        // use reflection to check that the save handler has a Redis
        // instance instantiated where one is not explicitly passed to
        // the constructor
        $redisClientProperty = new ReflectionProperty(FilteringSaveHandler::class, 'redisClient');
        $redisClientProperty->setAccessible(true);

        $saveHandler = new FilteringSaveHandler('tcp://localhost', 100);

        $this->assertInstanceOf(Redis::class, $redisClientProperty->getValue($saveHandler));
    }

    public function testConstructorIncludesTls(): void
    {
        // use reflection to check that the host extracted for a TLS
        // hostname includes the tls scheme
        $redisHostProperty = new ReflectionProperty(FilteringSaveHandler::class, 'redisHost');
        $redisHostProperty->setAccessible(true);

        $saveHandler = new FilteringSaveHandler('tls://localhost', 100);

        $this->assertSame('tls://localhost', $redisHostProperty->getValue($saveHandler));
    }

    public function testOpenException(): void
    {
        $expected = false;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andThrow(new RedisException());

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testOpenSuccess(): void
    {
        $expected = true;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(true);

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testWriteError(): void
    {
        $expected = false;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(false);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame($expected, $actual);
    }

    public function testWriteSuccess(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(true);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testWriteSuccessIgnored(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldNotReceive('setEx');

        // filter which always returns FALSE - i.e. write should be ignored
        $filter = function () {
            return false;
        };
        $this->saveHandler->addFilter($filter);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testGetError(): void
    {
        $expected = '';

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('get')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'baz')
            ->andReturn(false);

        $actual = $this->saveHandler->read('baz');

        $this->assertSame($expected, $actual);
    }

    public function testGetSuccess(): void
    {
        $expected = 'bar';

        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('get')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'baf')
            ->andReturn($expected);

        $actual = $this->saveHandler->read('baf');

        $this->assertSame($expected, $actual);
    }

    public function testClose()
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('close')
            ->andReturn(true);
        $this->assertSame(true, $this->saveHandler->close());
    }

    public function testDestroy()
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('del')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'boo')
            ->andReturn(true);
        $this->assertSame(true, $this->saveHandler->destroy('boo'));
    }

    public function testGc()
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $this->assertSame(1, $this->saveHandler->gc(1));
    }
}
