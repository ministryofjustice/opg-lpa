<?php

declare(strict_types=1);

namespace AppTest\Middleware\Session;

use App\Middleware\Session\JwtMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class JwtMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private const SECRET = 'test-secret-key-that-is-long-enough';

    private function makeMiddleware(): JwtMiddleware
    {
        return new JwtMiddleware(['secret' => self::SECRET]);
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::type(ServerRequestInterface::class))
            ->willReturn((new HttpFactory())->createResponse(200));
        return $handler->reveal();
    }

    private function makeRouteResult(bool $unauthenticated): RouteResult
    {
        $route = $this->prophesize(Route::class);
        $route->getOptions()->willReturn(
            $unauthenticated ? ['unauthenticated_route' => true] : []
        );

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn($route->reveal());
        return $result->reveal();
    }

    public function testThrowsOnHttpWhenRouteIsAuthenticated(): void
    {
        $middleware = $this->makeMiddleware();

        $request = (new ServerRequest([], [], 'http://example.com/home'))
            ->withAttribute(RouteResult::class, $this->makeRouteResult(false));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insecure use of middleware over http denied by configuration.');

        $middleware->process($request, $this->makeHandler());
    }

    public function testBypassesHttpCheckForUnauthenticatedRoute(): void
    {
        // Unauthenticated routes (e.g. /ping/elb) must be reachable over plain HTTP
        // without a JWT token — used by load balancer health checks.
        $middleware = $this->makeMiddleware();

        $request = (new ServerRequest([], [], 'http://example.com/ping/elb'))
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        $response = $middleware->process($request, $this->makeHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testBypassesJwtCheckForUnauthenticatedRoute(): void
    {
        // No JWT cookie or header present — should still pass through for unauthenticated routes.
        $middleware = $this->makeMiddleware();

        $request = (new ServerRequest([], [], 'https://example.com/ping/elb'))
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        $response = $middleware->process($request, $this->makeHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturns401WhenNoTokenOnAuthenticatedRoute(): void
    {
        $middleware = $this->makeMiddleware();

        // HTTPS but no JWT token in cookie or header
        $request = (new ServerRequest([], [], 'https://example.com/home'))
            ->withAttribute(RouteResult::class, $this->makeRouteResult(false));

        $response = $middleware->process($request, $this->makeHandler());

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testThrowsWhenNoRouteResultPresent(): void
    {
        // If RouteResult is absent (pre-routing), HTTP should still be denied.
        $middleware = $this->makeMiddleware();

        $request = new ServerRequest([], [], 'http://example.com/home');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insecure use of middleware over http denied by configuration.');

        $middleware->process($request, $this->makeHandler());
    }
}
