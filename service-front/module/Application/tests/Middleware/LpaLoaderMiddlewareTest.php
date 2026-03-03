<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class LpaLoaderMiddlewareTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private LpaLoaderMiddleware $middleware;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->middleware = new LpaLoaderMiddleware(
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(int $id, string $userId): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = $id;
        $lpa->user = $userId;
        $lpa->document = new Document();
        return $lpa;
    }

    private function createUserIdentity(string $userId): User&MockObject
    {
        $identity = $this->createMock(User::class);
        $identity->method('id')->willReturn($userId);
        return $identity;
    }

    private function handlerReturning(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        return $handler;
    }

    /**
     * Create a stub MiddlewareInterface for use as a Route's middleware argument.
     */
    private function stubMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                \Psr\Http\Message\ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    private function makeRouteResult(string $routeName, array $params = []): RouteResult
    {
        $route = new Route($routeName !== '' ? $routeName : '/', $this->stubMiddleware(), null, $routeName);
        return RouteResult::fromRoute($route, $params);
    }

    public function testPassesThroughWhenNoRouteResult(): void
    {
        $request = new ServerRequest();
        $expected = new EmptyResponse();
        $handler = $this->handlerReturning($expected);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expected, $result);
    }

    public function testPassesThroughWhenNoLpaId(): void
    {
        $routeResult = $this->makeRouteResult('lpa/form-type');
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expected = new EmptyResponse();

        $result = $this->middleware->process($request, $this->handlerReturning($expected));

        $this->assertSame($expected, $result);
    }

    public function testPassesThroughWhenNoUserIdentity(): void
    {
        $routeResult = $this->makeRouteResult('lpa/form-type', ['lpa-id' => '123']);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expected = new EmptyResponse();


        $result = $this->middleware->process($request, $this->handlerReturning($expected));

        $this->assertSame($expected, $result);
    }

    public function testReturns404WhenLpaNotFound(): void
    {
        $routeResult = $this->makeRouteResult('lpa/form-type', ['lpa-id' => '123']);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::IDENTITY, $this->createUserIdentity('user-123'));

        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn(false);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(HtmlResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testThrowsExceptionWhenUserDoesNotOwnLpa(): void
    {
        $routeResult = $this->makeRouteResult('lpa/form-type', ['lpa-id' => '123']);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::IDENTITY, $this->createUserIdentity('user-456'));

        $this->lpaApplicationService->method('getApplication')->willReturn($this->createLpa(123, 'user-123'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid LPA - current user can not access it');

        $this->middleware->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    public function testSetsRequestAttributesWhenRouteIsAccessible(): void
    {
        $lpa = $this->createLpa(123, 'user-123');

        $routeResult = $this->makeRouteResult('lpa/form-type', ['lpa-id' => '123']);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::IDENTITY, $this->createUserIdentity('user-123'));

        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $expected = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(function (ServerRequest $req) use ($expected, $lpa) {
                $this->assertSame($lpa, $req->getAttribute(RequestAttribute::LPA));
                $this->assertInstanceOf(FormFlowChecker::class, $req->getAttribute(RequestAttribute::FLOW_CHECKER));
                $this->assertEquals('lpa/form-type', $req->getAttribute(RequestAttribute::CURRENT_ROUTE));
                return $expected;
            });

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expected, $result);
    }

    public function testRedirectsWhenCalculatedRouteDiffers(): void
    {
        $lpa = $this->createLpa(123, 'user-123');

        // 'lpa/complete' is inaccessible before the form is filled in
        $routeResult = $this->makeRouteResult('lpa/complete', ['lpa-id' => '123']);
        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::IDENTITY, $this->createUserIdentity('user-123'));

        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/form-type');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }
}
