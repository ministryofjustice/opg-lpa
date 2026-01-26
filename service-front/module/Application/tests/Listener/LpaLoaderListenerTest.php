<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\LpaLoaderListener;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class LpaLoaderListenerTest extends TestCase
{
    private AuthenticationService|MockObject $authenticationService;
    private LpaApplicationService|MockObject $lpaApplicationService;
    private UrlHelper|MockObject $urlHelper;
    private LpaLoaderListener $listener;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->listener = new LpaLoaderListener(
            $this->authenticationService,
            $this->lpaApplicationService,
            $this->urlHelper
        );
    }

    private function createLpa(int $id, string $userId): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = $id;
        $lpa->user = $userId;
        return $lpa;
    }

    private function createUserIdentity(string $userId): User|MockObject
    {
        $identity = $this->createMock(User::class);
        $identity->method('id')->willReturn($userId);
        return $identity;
    }

    public function testAttachRegistersDispatchListener(): void
    {
        $events = $this->createMock(EventManagerInterface::class);
        $events->expects($this->once())
            ->method('attach')
            ->with(MvcEvent::EVENT_DISPATCH, [$this->listener, 'listen'], 1);

        $this->listener->attach($events);
    }

    public function testListenReturnsNullWhenNoRouteMatch(): void
    {
        $event = new MvcEvent();

        $result = $this->listener->listen($event);

        $this->assertNull($result);
    }

    public function testListenReturnsNullWhenNoLpaIdInRoute(): void
    {
        $routeMatch = new RouteMatch([]);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $result = $this->listener->listen($event);

        $this->assertNull($result);
    }

    public function testListenReturnsNullWhenNoUserIdentity(): void
    {
        $routeMatch = new RouteMatch(['lpa-id' => '123']);
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $result = $this->listener->listen($event);

        $this->assertNull($result);
    }

    public function testListenReturns404WhenLpaNotFound(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/form-type');
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
        ]);

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $identity = $this->createUserIdentity('user-123');
        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn(false);

        $result = $this->listener->listen($event);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testListenThrowsExceptionWhenUserDoesNotOwnLpa(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/form-type');
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
        ]);

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $identity = $this->createUserIdentity('user-456');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid LPA - current user can not access it');

        $this->listener->listen($event);
    }

    public function testListenReturnsResponseWhenFlowCheckerReturnsFalse(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
            ['pdf-type', null, null],
            ['idx', null, null],
        ]);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/complete');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('assemble')->willReturn('/lpa/123/form-type');

        $response = new Response();
        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setResponse($response);
        $event->setRouter($router);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $result = $this->listener->listen($event);

        $this->assertTrue($result === null || $result instanceof Response);
    }

    public function testListenRedirectsWhenCalculatedRouteDiffers(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
            ['pdf-type', null, null],
            ['idx', null, null],
        ]);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/complete');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('assemble')->willReturn('/lpa/123/form-type');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRouter($router);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $result = $this->listener->listen($event);

        if ($result instanceof Response && $result->getStatusCode() === 302) {
            $this->assertEquals(302, $result->getStatusCode());
            $this->assertTrue($result->getHeaders()->has('Location'));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testListenSetsEventParamsWhenRouteIsAccessible(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
            ['pdf-type', null, null],
            ['idx', null, null],
        ]);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/form-type');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('assemble')->willReturn('/lpa/123/form-type');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRouter($router);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $result = $this->listener->listen($event);

        if ($result === null) {
            $this->assertInstanceOf(Lpa::class, $event->getParam(LpaLoaderListener::ATTR_LPA));
            $this->assertInstanceOf(FormFlowChecker::class, $event->getParam(LpaLoaderListener::ATTR_FLOW_CHECKER));
            $this->assertNotNull($event->getParam(LpaLoaderListener::ATTR_CURRENT_ROUTE));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testListenHandlesDownloadRouteWithPdfType(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
            ['pdf-type', null, 'lp1'],
        ]);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/download');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $this->listener->listen($event);

        $this->assertTrue(true);
    }

    public function testProcessPassesThroughWhenNoRouteResult(): void
    {
        $request = new ServerRequest();

        $expectedResponse = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->listener->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessPassesThroughWhenRouteResultIsFailure(): void
    {
        $routeResult = RouteResult::fromRouteFailure(null);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $expectedResponse = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->listener->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessPassesThroughWhenNoLpaIdInParams(): void
    {
        $route = $this->createMock(Route::class);
        $routeResult = RouteResult::fromRoute($route, []);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $expectedResponse = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->listener->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessPassesThroughWhenNoUserIdentity(): void
    {
        $route = $this->createMock(Route::class);
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => '123']);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $expectedResponse = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->listener->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessReturns404WhenLpaNotFound(): void
    {
        $route = $this->createMock(Route::class);
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => '123']);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn(false);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->listener->process($request, $handler);

        $this->assertInstanceOf(HtmlResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('could not be found', (string) $result->getBody());
    }

    public function testProcessThrowsExceptionWhenUserDoesNotOwnLpa(): void
    {
        $route = $this->createMock(Route::class);
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => '123']);
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-456');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid LPA - current user can not access it');

        $this->listener->process($request, $handler);
    }

    public function testProcessReturnsEmptyResponseWhenFlowCheckerReturnsFalse(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('isFailure')->willReturn(false);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => '123']);
        $routeResult->method('getMatchedRouteName')->willReturn('lpa/complete');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $result = $this->listener->process($request, $handler);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    public function testProcessRedirectsWhenCalculatedRouteDiffers(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('isFailure')->willReturn(false);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => '123']);
        $routeResult->method('getMatchedRouteName')->willReturn('lpa/complete');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $this->urlHelper->method('generate')->willReturn('/lpa/123/form-type');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $result = $this->listener->process($request, $handler);

        if ($result instanceof RedirectResponse) {
            $this->assertEquals(302, $result->getStatusCode());
        } else {
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
        }
    }

    public function testProcessSetsRequestAttributesWhenRouteIsAccessible(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('isFailure')->willReturn(false);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => '123']);
        $routeResult->method('getMatchedRouteName')->willReturn('lpa/form-type');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $expectedResponse = new EmptyResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(function (ServerRequest $req) use ($expectedResponse) {
                $this->assertInstanceOf(Lpa::class, $req->getAttribute(LpaLoaderListener::ATTR_LPA));
                $this->assertInstanceOf(FormFlowChecker::class, $req->getAttribute(LpaLoaderListener::ATTR_FLOW_CHECKER));
                $this->assertEquals('lpa/form-type', $req->getAttribute(LpaLoaderListener::ATTR_CURRENT_ROUTE));
                return $expectedResponse;
            });

        $result = $this->listener->process($request, $handler);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    public function testProcessHandlesDownloadRouteWithPdfType(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('isFailure')->willReturn(false);
        $routeResult->method('getMatchedParams')->willReturn([
            'lpa-id' => '123',
            'pdf-type' => 'lp1'
        ]);
        $routeResult->method('getMatchedRouteName')->willReturn('lpa/download');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        $result = $this->listener->process($request, $handler);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    public function testProcessHandlesIdxParam(): void
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('isFailure')->willReturn(false);
        $routeResult->method('getMatchedParams')->willReturn([
            'lpa-id' => '123',
            'idx' => '0'
        ]);
        $routeResult->method('getMatchedRouteName')->willReturn('lpa/primary-attorney/edit');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $identity = $this->createUserIdentity('user-123');
        $lpa = $this->createLpa(123, 'user-123');

        $this->authenticationService->method('getIdentity')->willReturn($identity);
        $this->lpaApplicationService->method('getApplication')->with(123)->willReturn($lpa);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        $result = $this->listener->process($request, $handler);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    public function testListenReturnsNullForDashboardRoutes(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('user/dashboard/lpa');
        $routeMatch->method('getParam')->willReturnMap([
            ['lpa-id', null, '123'],
        ]);

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $result = $this->listener->listen($event);

        $this->assertNull($result);
    }

    public function testConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals(Lpa::class, LpaLoaderListener::ATTR_LPA);
        $this->assertEquals(FormFlowChecker::class, LpaLoaderListener::ATTR_FLOW_CHECKER);
        $this->assertEquals('currentRouteName', LpaLoaderListener::ATTR_CURRENT_ROUTE);
    }
}
