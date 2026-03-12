<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\RouteMatchMiddleware;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Router\RouteMatch;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteMatchMiddlewareTest extends TestCase
{
    private RouteMatchMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RouteMatchMiddleware();
    }

    private function handlerCapturing(?ServerRequest &$captured = null): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(function (ServerRequest $req) use (&$captured): ResponseInterface {
                $captured = $req;
                return new EmptyResponse();
            });
        return $handler;
    }

    public function testPassesThroughWhenNoRouteMatchAttribute(): void
    {
        $request = new ServerRequest();
        $handler = $this->handlerCapturing($captured);

        $this->middleware->process($request, $handler);

        // No RouteResult or CURRENT_ROUTE should have been added
        $this->assertNull($captured->getAttribute(RouteResult::class));
        $this->assertNull($captured->getAttribute(RequestAttribute::CURRENT_ROUTE));
    }

    public function testSkipsWhenRouteResultAlreadyPresent(): void
    {
        $existingResult = RouteResult::fromRouteFailure(null);
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('some/route');

        $request = (new ServerRequest())
            ->withAttribute(RouteMatch::class, $routeMatch)
            ->withAttribute(RouteResult::class, $existingResult);

        $handler = $this->handlerCapturing($captured);
        $this->middleware->process($request, $handler);

        // The pre-existing RouteResult must not be replaced
        $this->assertSame($existingResult, $captured->getAttribute(RouteResult::class));
    }

    public function testCreatesRouteResultFromRouteMatch(): void
    {
        $routeMatch = new RouteMatch(['lpa-id' => '42', 'action' => 'index']);
        $routeMatch->setMatchedRouteName('lpa/form-type');

        $request = (new ServerRequest())->withAttribute(RouteMatch::class, $routeMatch);

        $handler = $this->handlerCapturing($captured);
        $this->middleware->process($request, $handler);

        /** @var RouteResult $routeResult */
        $routeResult = $captured->getAttribute(RouteResult::class);
        $this->assertInstanceOf(RouteResult::class, $routeResult);
        $this->assertTrue($routeResult->isSuccess());
        $this->assertSame('lpa/form-type', $routeResult->getMatchedRouteName());
        $this->assertSame(['lpa-id' => '42', 'action' => 'index'], $routeResult->getMatchedParams());
        $this->assertSame('lpa/form-type', $captured->getAttribute(RequestAttribute::CURRENT_ROUTE));
    }

    public function testCarriesOverOptionKeys(): void
    {
        $routeMatch = new RouteMatch(['unauthenticated_route' => true, 'allowIncompleteUser' => true]);
        $routeMatch->setMatchedRouteName('user/about-you');

        $request = (new ServerRequest())->withAttribute(RouteMatch::class, $routeMatch);

        $handler = $this->handlerCapturing($captured);
        $this->middleware->process($request, $handler);

        /** @var RouteResult $routeResult */
        $routeResult = $captured->getAttribute(RouteResult::class);
        $options = $routeResult->getMatchedRoute()->getOptions();

        $this->assertTrue($options['unauthenticated_route']);
        $this->assertTrue($options['allowIncompleteUser']);
    }

    public function testIgnoresOptionKeysNotPresent(): void
    {
        $routeMatch = new RouteMatch(['lpa-id' => '1']);
        $routeMatch->setMatchedRouteName('lpa/form-type');

        $request = (new ServerRequest())->withAttribute(RouteMatch::class, $routeMatch);

        $handler = $this->handlerCapturing($captured);
        $this->middleware->process($request, $handler);

        /** @var RouteResult $routeResult */
        $routeResult = $captured->getAttribute(RouteResult::class);
        $options = $routeResult->getMatchedRoute()->getOptions();

        $this->assertArrayNotHasKey('unauthenticated_route', $options);
        $this->assertArrayNotHasKey('allowIncompleteUser', $options);
    }
}
