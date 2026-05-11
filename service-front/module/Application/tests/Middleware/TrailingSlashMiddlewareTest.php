<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\TrailingSlashMiddleware;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class TrailingSlashMiddlewareTest extends TestCase
{
    private TrailingSlashMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new TrailingSlashMiddleware();
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        return $handler;
    }

    public function testRedirectsTrailingSlashPath(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/login/');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://example.com/login', $response->getHeaderLine('Location'));
    }

    public function testRedirectsDeepTrailingSlashPath(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/lpa/123/donor/');

        $response = $this->middleware->process($request, $this->makeHandler());

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://example.com/lpa/123/donor', $response->getHeaderLine('Location'));
    }

    public function testPreservesQueryStringOnRedirect(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/login/?state=timeout');

        $response = $this->middleware->process($request, $this->makeHandler());

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://example.com/login?state=timeout', $response->getHeaderLine('Location'));
    }

    public function testDoesNotRedirectRootPath(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/');
        $expectedResponse = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testDoesNotRedirectPathWithoutTrailingSlash(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/login');
        $expectedResponse = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testPassesThroughResponseFromHandler(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $expectedResponse = new Response('php://memory', 200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
