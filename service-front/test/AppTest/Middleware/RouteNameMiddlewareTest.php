<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\RequestAttribute;
use App\Middleware\RouteNameMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Root-cause context
 * ------------------
 * In Laminas MVC, controllers received the matched route name implicitly via
 *   $this->getEvent()->getRouteMatch()->getMatchedRouteName()
 *
 * In Mezzio, the name lives in RouteResult (set by RouteMiddleware).
 * RouteNameMiddleware bridges the gap by extracting it and setting it as
 * RequestAttribute::CURRENT_ROUTE_NAME so all 31+ handlers that depend on it
 * work without changes to their call-sites.
 */
final class RouteNameMiddlewareTest extends TestCase
{
    private RouteNameMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RouteNameMiddleware();
    }

    private function makeHandler(?string $expectedRouteName): RequestHandlerInterface
    {
        return new class ($expectedRouteName) implements RequestHandlerInterface {
            public ?string $capturedRouteName = null;

            public function __construct(private readonly ?string $expected)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->capturedRouteName = $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);
                return new EmptyResponse();
            }
        };
    }

    public function testSetsCurrentRouteNameFromMatchedRouteResult(): void
    {
        $routeResult = RouteResult::fromRoute(
            new Route('/lpa/{lpa-id}/applicant', new \Laminas\Stratigility\MiddlewarePipe(), ['GET'], 'lpa/applicant'),
        );

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $handler = $this->makeHandler('lpa/applicant');

        $this->middleware->process($request, $handler);

        $this->assertSame('lpa/applicant', $handler->capturedRouteName);
    }

    public function testSetsCorrectNameForDifferentRoute(): void
    {
        $routeResult = RouteResult::fromRoute(
            new Route('/user/dashboard', new \Laminas\Stratigility\MiddlewarePipe(), ['GET'], 'user/dashboard'),
        );

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $handler = $this->makeHandler('user/dashboard');

        $this->middleware->process($request, $handler);

        $this->assertSame('user/dashboard', $handler->capturedRouteName);
    }

    public function testDoesNotSetAttributeWhenNoRouteResult(): void
    {
        $request = new ServerRequest();
        $handler = $this->makeHandler(null);

        $this->middleware->process($request, $handler);

        $this->assertNull($handler->capturedRouteName);
    }

    public function testDoesNotSetAttributeOnRouteFailure(): void
    {
        $routeResult = RouteResult::fromRouteFailure(['GET']);

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $handler = $this->makeHandler(null);

        $this->middleware->process($request, $handler);

        $this->assertNull($handler->capturedRouteName);
    }

    public function testPassesThroughResponse(): void
    {
        $request = new ServerRequest();
        $response = new EmptyResponse(204);

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response)
            {
            }
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }
}
