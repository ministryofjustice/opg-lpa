<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\RouteNotFoundMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteNotFoundMiddlewareTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private RouteNotFoundMiddleware $middleware;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->middleware = new RouteNotFoundMiddleware($this->renderer);
    }

    public function testRendersNotFoundPageWhenRouteMatchFailed(): void
    {
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, RouteResult::fromRouteFailure(null));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(RouteNotFoundMiddleware::TEMPLATE_404)
            ->willReturn('<h1>Page not found</h1>');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('<h1>Page not found</h1>', (string) $response->getBody());
    }

    public function testDelegatesToHandlerWhenMethodNotAllowed(): void
    {
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, RouteResult::fromRouteFailure(['GET']));
        $expected = new EmptyResponse(405);

        $this->renderer->expects($this->never())->method('render');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expected);

        $this->assertSame($expected, $this->middleware->process($request, $handler));
    }

    public function testDelegatesToHandlerWhenRouteMatched(): void
    {
        $routeResult = RouteResult::fromRoute(
            new Route('/home', new MiddlewarePipe(), ['GET'], 'home'),
        );
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expected = new EmptyResponse(200);

        $this->renderer->expects($this->never())->method('render');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expected);

        $this->assertSame($expected, $this->middleware->process($request, $handler));
    }
}
