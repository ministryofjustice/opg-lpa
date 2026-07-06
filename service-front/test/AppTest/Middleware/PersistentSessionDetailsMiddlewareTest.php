<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\PersistentSessionDetailsMiddleware;
use App\Model\Service\Session\PersistentSessionDetails;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class PersistentSessionDetailsMiddlewareTest extends TestCase
{
    private PersistentSessionDetails&MockObject $persistentSessionDetails;
    private SessionInterface&MockObject $session;

    protected function setUp(): void
    {
        $this->persistentSessionDetails = $this->createMock(PersistentSessionDetails::class);
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testProcessRefreshesSessionDetailsAndPassesThrough(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
        $expectedResponse = new PSR7Response();

        $this->persistentSessionDetails->expects($this->once())
            ->method('refresh')
            ->with($routeResult, $this->session);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new PersistentSessionDetailsMiddleware($this->persistentSessionDetails))
            ->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessPassesNullRouteResultToRefresh(): void
    {
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
        $expectedResponse = new PSR7Response();

        $this->persistentSessionDetails->expects($this->once())
            ->method('refresh')
            ->with(null, $this->session);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new PersistentSessionDetailsMiddleware($this->persistentSessionDetails))
            ->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
