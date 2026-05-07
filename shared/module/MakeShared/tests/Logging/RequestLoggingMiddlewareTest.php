<?php

declare(strict_types=1);

namespace MakeSharedTest\Logging;

use MakeShared\Constants;
use MakeShared\Logging\RequestLoggingMiddleware;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;

class RequestLoggingMiddlewareTest extends TestCase
{
    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        return $handler;
    }

    private function makeRecord(array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: [],
            extra: $extra,
        );
    }

    public function testPushesProcessorWhenLoggerIsMonolog(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('pushProcessor')
            ->with($this->isType('callable'))
            ->willReturnSelf();

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process(new ServerRequest(), $this->makeHandler());
    }

    public function testDoesNotPushProcessorForNonMonologLogger(): void
    {
        $logger = new NullLogger();
        $middleware = new RequestLoggingMiddleware($logger);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle');

        $middleware->process(new ServerRequest(uri: 'https://example.com/path'), $handler);
    }

    public function testProcessorAddsRequestPathAndMethod(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process(
            new ServerRequest(method: 'POST', uri: 'https://example.com/login'),
            $this->makeHandler()
        );

        $result = $capturedProcessor($this->makeRecord());

        $this->assertInstanceOf(LogRecord::class, $result);
        $this->assertSame('/login', $result->extra['request_path']);
        $this->assertSame('POST', $result->extra['request_method']);
    }

    public function testProcessorUsesXTraceIdHeader(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $request = (new ServerRequest(uri: 'https://example.com/path'))
            ->withHeader('X-Trace-Id', 'trace-abc-123');

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process($request, $this->makeHandler());

        $result = $capturedProcessor($this->makeRecord());

        $this->assertSame('trace-abc-123', $result->extra[Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testProcessorFallsBackToXRequestIdWhenXTraceIdAbsent(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $request = (new ServerRequest(uri: 'https://example.com/path'))
            ->withHeader('X-Request-ID', 'request-xyz');

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process($request, $this->makeHandler());

        $result = $capturedProcessor($this->makeRecord());

        $this->assertSame('request-xyz', $result->extra[Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testProcessorSetsNotAvailableWhenBothHeadersAbsent(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process(new ServerRequest(uri: 'https://example.com/path'), $this->makeHandler());

        $result = $capturedProcessor($this->makeRecord());

        $this->assertSame('not available', $result->extra[Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testDelegatesRequestToHandler(): void
    {
        $logger = new NullLogger();
        $middleware = new RequestLoggingMiddleware($logger);

        $request = new ServerRequest();
        $expectedResponse = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);
        $this->assertSame($expectedResponse, $response);
    }
}
