<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\RequestLoggingMiddleware;
use MakeShared\Constants;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Monolog\Logger;
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
        // NullLogger implements LoggerInterface but is not a Monolog\Logger instance
        $logger = new NullLogger();

        $middleware = new RequestLoggingMiddleware($logger);

        // No exception and handler is still called
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

        $record = $capturedProcessor(['extra' => []]);

        $this->assertSame('/login', $record['extra']['request_path']);
        $this->assertSame('POST', $record['extra']['request_method']);
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

        $record = $capturedProcessor(['extra' => []]);

        $this->assertArrayHasKey(Constants::TRACE_ID_FIELD_NAME, $record['extra']);
        $this->assertSame('trace-abc-123', $record['extra'][Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testProcessorDoesNotAddTraceIdWhenHeaderAbsent(): void
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

        $record = $capturedProcessor(['extra' => []]);

        $this->assertArrayNotHasKey(Constants::TRACE_ID_FIELD_NAME, $record['extra']);
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
