<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\RequestLoggingMiddleware;
use MakeShared\Constants;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle');

        $middleware->process($request, $handler);
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

        $request = new ServerRequest(
            method: 'POST',
            uri: 'https://example.com/login',
        );

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process($request, $this->makeHandler());

        $this->assertNotNull($capturedProcessor);

        $result = $capturedProcessor($this->makeRecord());

        $this->assertInstanceOf(LogRecord::class, $result);
        $this->assertSame('/login', $result->extra['request_path']);
        $this->assertSame('POST', $result->extra['request_method']);
    }

    public function testProcessorAddsTraceIdWhenHeaderPresent(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $request = (new ServerRequest(uri: 'https://example.com/dashboard'))
            ->withHeader('X-Request-ID', 'trace-abc-123');

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process($request, $this->makeHandler());

        $result = $capturedProcessor($this->makeRecord());

        $this->assertInstanceOf(LogRecord::class, $result);
        $this->assertArrayHasKey(Constants::TRACE_ID_FIELD_NAME, $result->extra);
        $this->assertSame('trace-abc-123', $result->extra[Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testProcessorSetsEmptyTraceIdWhenHeaderAbsent(): void
    {
        $capturedProcessor = null;

        $logger = $this->createMock(Logger::class);
        $logger->method('pushProcessor')
            ->willReturnCallback(function (callable $processor) use (&$capturedProcessor, $logger): Logger {
                $capturedProcessor = $processor;
                return $logger;
            });

        $request = new ServerRequest(uri: 'https://example.com/dashboard');

        $middleware = new RequestLoggingMiddleware($logger);
        $middleware->process($request, $this->makeHandler());

        $result = $capturedProcessor($this->makeRecord());

        $this->assertInstanceOf(LogRecord::class, $result);
        $this->assertArrayHasKey(Constants::TRACE_ID_FIELD_NAME, $result->extra);
        $this->assertSame('', $result->extra[Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testPassesThroughResponseFromHandler(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $expectedResponse = new Response();

        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $middleware = new RequestLoggingMiddleware($logger);
        $response = $middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
