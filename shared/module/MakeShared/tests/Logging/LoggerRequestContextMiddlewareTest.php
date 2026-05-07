<?php

declare(strict_types=1);

namespace MakeSharedTest\Logging;

use MakeShared\Constants;
use MakeShared\Logging\LoggerRequestContextMiddleware;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggerRequestContextMiddlewareTest extends TestCase
{
    private function makeRequest(string $method, string $path, array $headers = []): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);
        $request->method('getHeaderLine')->willReturnCallback(
            fn(string $name) => $headers[$name] ?? ''
        );

        return $request;
    }

    public function testSetsRequestPathAndMethod(): void
    {
        $logger = new Logger('test');
        $middleware = new LoggerRequestContextMiddleware($logger);

        $request = $this->makeRequest('GET', '/some/path');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $middleware->process($request, $handler);

        $processors = $logger->getProcessors();
        $this->assertCount(1, $processors);

        $record = ($processors[0])(['extra' => []]);
        $this->assertEquals('/some/path', $record['extra']['request_path']);
        $this->assertEquals('GET', $record['extra']['request_method']);
    }

    public function testUsesXTraceIdHeader(): void
    {
        $logger = new Logger('test');
        $middleware = new LoggerRequestContextMiddleware($logger);

        $request = $this->makeRequest('POST', '/path', ['X-Trace-Id' => 'trace-abc']);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $middleware->process($request, $handler);

        $record = ($logger->getProcessors()[0])(['extra' => []]);
        $this->assertEquals('trace-abc', $record['extra'][Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testFallsBackToXRequestIdWhenXTraceIdAbsent(): void
    {
        $logger = new Logger('test');
        $middleware = new LoggerRequestContextMiddleware($logger);

        $request = $this->makeRequest('GET', '/path', ['X-Request-ID' => 'request-xyz']);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $middleware->process($request, $handler);

        $record = ($logger->getProcessors()[0])(['extra' => []]);
        $this->assertEquals('request-xyz', $record['extra'][Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testSetsNotAvailableWhenBothHeadersAbsent(): void
    {
        $logger = new Logger('test');
        $middleware = new LoggerRequestContextMiddleware($logger);

        $request = $this->makeRequest('GET', '/path');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $middleware->process($request, $handler);

        $record = ($logger->getProcessors()[0])(['extra' => []]);
        $this->assertEquals('not available', $record['extra'][Constants::TRACE_ID_FIELD_NAME]);
    }

    public function testDelegatesRequestToHandler(): void
    {
        $logger = new Logger('test');
        $middleware = new LoggerRequestContextMiddleware($logger);

        $request = $this->makeRequest('GET', '/path');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);
        $this->assertSame($expectedResponse, $response);
    }
}
