<?php

declare(strict_types=1);

namespace AppTest\Middleware\Authorization;

use App\Middleware\Authorization\AuthorizationMiddleware;
use App\Service\Authentication\AuthenticationService;
use App\Service\User\UserService;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Permissions\Rbac\Rbac;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private function makeMiddleware(): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware(
            $this->prophesize(AuthenticationService::class)->reveal(),
            $this->prophesize(UserService::class)->reveal(),
            $this->prophesize(UrlHelper::class)->reveal(),
            new Rbac(),
            $this->prophesize(NotFoundHandler::class)->reveal(),
        );
    }

    private function makeHandler(int $statusCode = 200): RequestHandlerInterface
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(\Prophecy\Argument::type(ServerRequestInterface::class))
            ->willReturn((new HttpFactory())->createResponse($statusCode));
        return $handler->reveal();
    }

    private function makeRouteResult(bool $unauthenticated, string $routeName = 'some.route'): RouteResult
    {
        $route = $this->prophesize(Route::class);
        $route->getOptions()->willReturn(
            $unauthenticated ? ['unauthenticated_route' => true] : []
        );
        $route->getName()->willReturn($routeName);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn($route->reveal());
        return $result->reveal();
    }

    public function testBypassesAllChecksForUnauthenticatedRoute(): void
    {
        // Unauthenticated routes (e.g. /ping/elb) must pass through without any
        // JWT session data read or RBAC check — no $_SESSION['jwt-payload'] needed.
        $middleware = $this->makeMiddleware();

        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        $response = $middleware->process($request, $this->makeHandler(200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUnauthenticatedRouteDoesNotRequireSessionData(): void
    {
        // Specifically: $_SESSION['jwt-payload'] must NOT be required for unauthenticated routes.
        // If the middleware tries to read it, JwtTrait::verifyTokenDataExists() would throw.
        $middleware = $this->makeMiddleware();

        // Ensure no session data is present
        unset($_SESSION['jwt-payload']);

        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        // Should not throw RuntimeException("JWT token not available")
        $response = $middleware->process($request, $this->makeHandler(200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPassesThroughToHandlerForUnauthenticatedRoute(): void
    {
        // Downstream handler response must be returned unchanged.
        $middleware = $this->makeMiddleware();

        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $this->makeRouteResult(true));

        $response = $middleware->process($request, $this->makeHandler(204));

        $this->assertSame(204, $response->getStatusCode());
    }
}
