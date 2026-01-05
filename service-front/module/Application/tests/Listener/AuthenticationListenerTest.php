<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\AuthenticationListener;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response as MVCResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationListenerTest extends TestCase
{
    private SessionUtility|MockObject $sessionUtility;
    private AuthenticationService|MockObject $authenticationService;
    private EventManagerInterface|MockObject $eventManager;

    public function setUp(): void
    {
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
    }

    public function testAttach(): void
    {
        $expectedFn = function () {
        };

        $this->eventManager
            ->expects($this->once())
            ->method('attach')
            ->with(
                MvcEvent::EVENT_DISPATCH,
                $this->callback(function ($arg) {
                    return is_array($arg)
                        && count($arg) === 2
                        && $arg[0] instanceof AuthenticationListener
                        && $arg[1] === 'listen';
                }),
                1
            )
            ->willReturn($expectedFn);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $listener->attach($this->eventManager);
    }

    public function testListenWhenNoRouteMatch(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn(null);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUnauthenticatedRoute(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(true);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUserIsAuthenticated(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('2001-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUnauthenticatedWithTimeoutReason(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);
        $routeMatch
            ->method('getMatchedRouteName')
            ->willReturn('some/route');

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', 'some/route');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->method('assemble')
            ->with(['state' => 'timeout'], ['name' => 'login'])
            ->willReturn('/login?state=timeout');

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->method('getRouter')
            ->willReturn($router);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals('/login?state=timeout', $result->getHeaders()->get('Location')->getFieldValue());
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testListenWhenUnauthenticatedWithInternalSystemError(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);
        $routeMatch
            ->method('getMatchedRouteName')
            ->willReturn('some/route');

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', 'some/route');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(500);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->method('assemble')
            ->with(['state' => 'internalSystemError'], ['name' => 'login'])
            ->willReturn('/login?state=internalSystemError');

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->method('getRouter')
            ->willReturn($router);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals('/login?state=internalSystemError', $result->getHeaders()->get('Location')->getFieldValue());
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testListenWhenUnauthenticatedOnUserDeleteRoute(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);
        $routeMatch
            ->method('getMatchedRouteName')
            ->willReturn('user/delete');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->method('assemble')
            ->with(['state' => 'timeout'], ['name' => 'login'])
            ->willReturn('/login?state=timeout');

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->method('getRouter')
            ->willReturn($router);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testListenWhenUnauthenticatedOnUserDashboardRoute(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);
        $routeMatch
            ->method('getMatchedRouteName')
            ->willReturn('user/dashboard/settings');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->method('assemble')
            ->with(['state' => 'timeout'], ['name' => 'login'])
            ->willReturn('/login?state=timeout');

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->method('getRouter')
            ->willReturn($router);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testProcessWhenNoRoute(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenRouteDoesNotRequireAuth(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['unauthenticated_route' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenUserIsAuthenticated(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('2001-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenUnauthenticatedWithTimeoutReason(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);
        $route
            ->method('getPath')
            ->willReturn('/some/path');

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);
        $routeResult
            ->method('getMatchedRouteName')
            ->willReturn('some/route');

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', '/some/path');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->method('generate')
            ->with('login', [], ['state' => 'timeout'])
            ->willReturn('/login?state=timeout');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login?state=timeout', $result->getHeaderLine('Location'));
    }

    public function testProcessWhenUnauthenticatedWithInternalSystemError(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);
        $route
            ->method('getPath')
            ->willReturn('/some/path');

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);
        $routeResult
            ->method('getMatchedRouteName')
            ->willReturn('some/route');

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', '/some/path');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(500);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->method('generate')
            ->with('login', [], ['state' => 'internalSystemError'])
            ->willReturn('/login?state=internalSystemError');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login?state=internalSystemError', $result->getHeaderLine('Location'));
    }

    public function testProcessWhenUnauthenticatedOnUserDeleteRoute(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);
        $route
            ->method('getPath')
            ->willReturn('/some/path');

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);
        $routeResult
            ->method('getMatchedRouteName')
            ->willReturn('user/delete');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->method('generate')
            ->with('login', [], ['state' => 'timeout'])
            ->willReturn('/login?state=timeout');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login?state=timeout', $result->getHeaderLine('Location'));
    }

    public function testProcessWhenUnauthenticatedOnUserDashboardRoute(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);
        $route
            ->method('getPath')
            ->willReturn('/user/dashboard/settings');

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);
        $routeResult
            ->method('getMatchedRouteName')
            ->willReturn('user/dashboard/settings');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn(null);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->method('generate')
            ->with('login', [], ['state' => 'timeout'])
            ->willReturn('/login?state=timeout');

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login?state=timeout', $result->getHeaderLine('Location'));
    }
}
