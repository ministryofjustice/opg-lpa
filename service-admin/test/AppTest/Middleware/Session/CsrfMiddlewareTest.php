<?php

declare(strict_types=1);

namespace AppTest\Middleware\Session;

use App\Middleware\Session\CsrfMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private function makeHandler(int $status = 200): RequestHandlerInterface
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::type(ServerRequestInterface::class))
            ->willReturn((new HttpFactory())->createResponse($status));
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

    public function testBypassesJwtSessionForUnauthenticatedRoute(): void
    {
        // Unauthenticated routes have no $_SESSION['jwt-payload'] — must not throw.
        $middleware = new CsrfMiddleware();

        unset($_SESSION['jwt-payload']);

        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        $response = $middleware->process($request, $this->makeHandler(200));

        $this->assertSame(200, $response->getStatusCode());
    }
}
