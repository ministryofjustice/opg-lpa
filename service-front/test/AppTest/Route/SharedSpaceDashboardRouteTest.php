<?php

declare(strict_types=1);

namespace AppTest\Route;

use App\Handler\SharedSpaceDashboardHandler;
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

class SharedSpaceDashboardRouteTest extends TestCase
{
    private const string ROUTE_NAME = 'shared-space.dashboard.pagination';

    private FastRouteRouter $router;

    protected function setUp(): void
    {
        putenv('SHARED_SPACES_ENABLED=true');

        $this->router = new FastRouteRouter();

        $container = $this->createMock(ContainerInterface::class);
        $factory   = new MiddlewareFactory(new MiddlewareContainer($container));

        $app = new Application(
            $factory,
            new MiddlewarePipe(),
            new RouteCollector($this->router),
            $this->createMock(RequestHandlerRunnerInterface::class),
        );

        (require __DIR__ . '/../../../config/routes.php')($app, $factory, $container);
    }

    protected function tearDown(): void
    {
        putenv('SHARED_SPACES_ENABLED');

        parent::tearDown();
    }

    private function match(string $path): RouteResult
    {
        return $this->router->match(
            (new ServerRequest())->withMethod('GET')->withUri(new Uri('http://localhost' . $path))
        );
    }

    public function testPageLinkReachesTheSharedSpaceDashboardWithThePageNumber(): void
    {
        $result = $this->match('/shared-space/dashboard/page/2');

        $this->assertTrue($result->isSuccess(), 'Expected page 2 of the shared space dashboard to match');
        $this->assertSame(self::ROUTE_NAME, $result->getMatchedRouteName());
        $this->assertSame('2', $result->getMatchedParams()['page'] ?? null);

        $route = $result->getMatchedRoute();
        $this->assertInstanceOf(Route::class, $route);

        $middleware = $route->getMiddleware();
        $name = (new \ReflectionProperty($middleware, 'middlewareName'))->getValue($middleware);

        $this->assertSame(SharedSpaceDashboardHandler::class, $name);
    }

    public function testPageRouteIsLimitedToSharedSpaceMembers(): void
    {
        $route = $this->match('/shared-space/dashboard/page/2')->getMatchedRoute();

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['member', 'admin'], $route->getOptions()['sharedSpaceAllow'] ?? null);
    }

    public function testGeneratesPageLinks(): void
    {
        $this->assertSame(
            '/shared-space/dashboard/page/3',
            $this->router->generateUri(self::ROUTE_NAME, ['page' => 3]),
        );
    }
}
