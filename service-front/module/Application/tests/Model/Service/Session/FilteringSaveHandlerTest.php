<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\Session\FilteringSaveHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class FilteringSaveHandlerTest extends MockeryTestCase
{
    private FilteringSaveHandler $saveHandler;

    private function makeSaveHandlerWithMock(): RedisClient
    {
        $redisMock = Mockery::Mock(RedisClient::class);
        $this->saveHandler = new FilteringSaveHandler($redisMock, []);
        return $redisMock;
    }

    public function tearDown(): void
    {
        Mockery::close();
        unset($this->saveHandler);
    }

    public function testConstructorWithFiltersCausesIgnore(): void
    {
        $redisMock = Mockery::Mock(Redis::class);

        // redisMock should not receive a call to set the value,
        // as the filters should prevent the write from happening
        $redisMock->shouldNotReceive('write')->with(
            FilteringSaveHandler::SESSION_PREFIX . 'foo',
            'bar'
        )->andReturn(true);

        $filter = function (): false {
            return false;
        };
        $saveHandler = new FilteringSaveHandler($redisMock, [$filter]);
        $actual = $saveHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testOpenFailure(): void
    {
        $expected = false;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('open')->andReturn(false);

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testOpenSuccess(): void
    {
        $expected = true;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('open')->andReturn(true);

        $actual = $this->saveHandler->open('ignoredSavePath', 'ignoredSessionName');

        $this->assertSame($expected, $actual);
    }

    public function testWriteError(): void
    {
        $expected = false;

        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('write')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 'bar')
            ->andReturn(false);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame($expected, $actual);
    }

    public function testWriteSuccess(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldReceive('write')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'foo', 'bar')
            ->andReturn(true);

        $actual = $this->saveHandler->write('foo', 'bar');

        $this->assertSame(true, $actual);
    }

    public function testWriteSuccessIgnored(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://foohost');
        $redisMock->shouldNotReceive('setEx');

        // filter which always returns FALSE - i.e. write should be ignored
        $filter = function (): false {
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
        $redisMock->shouldReceive('read')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'baz')
            ->andReturn(false);

        $actual = $this->saveHandler->read('baz');

        $this->assertSame($expected, $actual);
    }

    public function testGetSuccess(): void
    {
        $expected = 'bar';

        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('read')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'baf')
            ->andReturn($expected);

        $actual = $this->saveHandler->read('baf');

        $this->assertSame($expected, $actual);
    }

    public function testClose(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('close')
            ->andReturn(true);
        $this->assertSame(true, $this->saveHandler->close());
    }

    public function testDestroy(): void
    {
        $redisMock = $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('destroy')
            ->with(FilteringSaveHandler::SESSION_PREFIX . 'boo')
            ->andReturn(true);
        $this->assertSame(true, $this->saveHandler->destroy('boo'));
    }

    public function testGc(): void
    {
        $this->makeSaveHandlerWithMock('tcp://barhost:6737');
        $this->assertSame(1, $this->saveHandler->gc(1));
    }
}
