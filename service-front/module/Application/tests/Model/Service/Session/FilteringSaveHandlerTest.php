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
    // returns the mock for setting expectations
    private function makeSaveHandlerWithMock(string $redisUrl): Redis {
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
        $redisMock->shouldNotReceive('setEx')->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 100, 'bar')->andReturn(TRUE);

        $filter = function () {
            return FALSE;
        };
        $saveHandler = new FilteringSaveHandler('tcp://localhost', 100, [$filter], $redisMock);
        $actual = $saveHandler->write('foo', 'bar');

        $this->assertSame(TRUE, $actual);
        $this->assertSame(0, $saveHandler->sessionWritesAttempted);
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
        $expected = FALSE;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andThrow(new RedisException());

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testOpenSuccess(): void
    {
        $expected = TRUE;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(TRUE);

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testWriteError(): void
    {
        $expected = FALSE;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(FALSE);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame($expected, $actual);
        $this->assertSame(1, $this->saveHandler->sessionWritesAttempted);
    }

    public function testWriteSuccess(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('setEx')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 1000, 'bar')
            ->andReturn(TRUE);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame(TRUE, $actual);
        $this->assertSame(1, $this->saveHandler->sessionWritesAttempted);
    }

    public function testWriteSuccessIgnored(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldNotReceive('setEx');

        // filter which always returns FALSE - i.e. write should be ignored
        $filter = function () {
            return FALSE;
        };
        $this->saveHandler->addFilter($filter);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame(TRUE, $actual);
        $this->assertSame(0, $this->saveHandler->sessionWritesAttempted);
    }

    public function testGetError(): void
    {
        $expected = '';

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('get')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'baz')
            ->andReturn(FALSE);

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
            ->andReturn(TRUE);
        $this->assertSame(TRUE, $this->saveHandler->close());
    }

    public function testDestroy()
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('del')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'boo')
            ->andReturn(TRUE);
        $this->assertSame(TRUE, $this->saveHandler->destroy('boo'));
    }

    public function testGc()
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $this->assertSame(1, $this->saveHandler->gc(1));
    }
}