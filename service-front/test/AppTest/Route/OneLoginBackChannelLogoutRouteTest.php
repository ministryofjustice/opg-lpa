<?php

declare(strict_types=1);

namespace AppTest\Route;

use App\Handler\OneLoginBackChannelLogoutHandler;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class OneLoginBackChannelLogoutRouteTest extends TestCase
{
    private const string PATH = 'http://localhost/auth/onelogin/backchannel-logout';

    private function routerWithOneLogin(bool $enabled): FastRouteRouter
    {
        putenv('ONELOGIN_ENABLED=' . ($enabled ? 'true' : 'false'));

        $router    = new FastRouteRouter();
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new MiddlewareFactory(new MiddlewareContainer($container));

        $app = new Application(
            $factory,
            new MiddlewarePipe(),
            new RouteCollector($router),
            $this->createMock(RequestHandlerRunnerInterface::class),
        );

        (require __DIR__ . '/../../../config/routes.php')($app, $factory, $container);

        return $router;
    }

    private function match(FastRouteRouter $router, string $method): RouteResult
    {
        return $router->match(
            (new ServerRequest())->withMethod($method)->withUri(new Uri(self::PATH))
        );
    }

    protected function tearDown(): void
    {
        putenv('ONELOGIN_ENABLED');

        parent::tearDown();
    }

    public function testOneLoginsPostReachesTheBackChannelLogoutHandler(): void
    {
        $result = $this->match($this->routerWithOneLogin(true), 'POST');

        $this->assertTrue($result->isSuccess(), 'One Login POST should match a route');
        $this->assertSame('auth.onelogin.backchannel-logout', $result->getMatchedRouteName());

        $route = $result->getMatchedRoute();
        $this->assertInstanceOf(Route::class, $route);

        $middleware = $route->getMiddleware();
        $name = (new \ReflectionProperty($middleware, 'middlewareName'))->getValue($middleware);

        $this->assertSame(OneLoginBackChannelLogoutHandler::class, $name);
    }

    public function testRouteIsExemptFromCsrfAndAuthentication(): void
    {
        $result = $this->match($this->routerWithOneLogin(true), 'POST');

        $route = $result->getMatchedRoute();
        $this->assertInstanceOf(Route::class, $route);
        $this->assertTrue(
            ($route->getOptions()['unauthenticated_route'] ?? false) === true,
            'Route must be marked unauthenticated or CSRF validation will reject One Login',
        );
    }

    public function testGetIsNotAllowed(): void
    {
        $result = $this->match($this->routerWithOneLogin(true), 'GET');

        $this->assertTrue($result->isFailure());
    }

    public function testRouteIsAbsentWhenOneLoginIsDisabled(): void
    {
        $result = $this->match($this->routerWithOneLogin(false), 'POST');

        $this->assertTrue($result->isFailure(), 'Route must not exist while the flag is off');
    }
}
