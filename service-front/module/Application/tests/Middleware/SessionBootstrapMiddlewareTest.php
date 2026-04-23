<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\SessionBootstrapMiddleware;
use Application\Model\Service\Session\NativeSessionConfig;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class SessionBootstrapMiddlewareTest extends TestCase
{
    private NativeSessionConfig $nativeSessionConfig;
    private SessionManager&MockObject $sessionManager;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionBootstrapMiddleware $middleware;

    protected function setUp(): void
    {
        // NativeSessionConfig is final — construct a real instance with a mock save handler.
        $saveHandler = $this->createMock(SaveHandlerInterface::class);
        $this->nativeSessionConfig   = new NativeSessionConfig([], $saveHandler);
        $this->sessionManager        = $this->createMock(SessionManager::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);

        $this->middleware = new SessionBootstrapMiddleware(
            $this->nativeSessionConfig,
            $this->sessionManager,
            $this->sessionManagerSupport,
        );
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        return $handler;
    }

    public function testBootstrapsSessionForNormalRequest(): void
    {
        $this->sessionManager->expects($this->once())->method('start');
        $this->sessionManagerSupport->expects($this->once())->method('initialise');

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testSkipsSessionForPingElb(): void
    {
        $this->sessionManager->expects($this->never())->method('start');
        $this->sessionManagerSupport->expects($this->never())->method('initialise');

        $request = new ServerRequest(uri: 'https://example.com/ping/elb');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testSkipsSessionForPingJson(): void
    {
        $this->sessionManager->expects($this->never())->method('start');
        $this->sessionManagerSupport->expects($this->never())->method('initialise');

        $request = new ServerRequest(uri: 'https://example.com/ping/json');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testPassesThroughToHandler(): void
    {
        $expectedResponse = new Response();
        $request = new ServerRequest(uri: 'https://example.com/login');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testHandlerReceivesUnmodifiedRequestForExcludedPath(): void
    {
        $request = new ServerRequest(uri: 'https://example.com/ping/elb');
        $expectedResponse = new Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
