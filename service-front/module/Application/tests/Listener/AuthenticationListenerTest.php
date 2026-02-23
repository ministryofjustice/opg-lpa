<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\Attribute;
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
            ->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn(null);

        $this->authenticationService
            ->expects($this->never())
            ->method('getIdentity');

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertNull($result);
    }

    public function testListenWhenUnauthenticatedRoute(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->expects($this->once())
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(true);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn($identity);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->expects($this->once())
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($routeMatch);

        // Verify that identity is set as event param
        $event
            ->expects($this->once())
            ->method('setParam')
            ->with(Attribute::IDENTITY, $identity);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen($event));
    }

    public static function unauthenticatedListenDataProvider(): array
    {
        return [
            'timeout reason' => [
                'routeName' => 'some/route',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => true,
            ],
            'internal system error' => [
                'routeName' => 'some/route',
                'authFailureCode' => 500,
                'expectedState' => 'internalSystemError',
                'shouldSetPreAuthUrl' => true,
            ],
            'user delete route' => [
                'routeName' => 'user/delete',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
            'user dashboard route' => [
                'routeName' => 'user/dashboard/settings',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
        ];
    }

    /**
     * @dataProvider unauthenticatedListenDataProvider
     */
    public function testListenWhenUnauthenticated(
        string $routeName,
        ?int $authFailureCode,
        string $expectedState,
        bool $shouldSetPreAuthUrl
    ): void {
        $this->authenticationService
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn(null);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->expects($this->once())
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);
        $routeMatch
            ->expects($this->once())
            ->method('getMatchedRouteName')
            ->willReturn($routeName);

        if ($shouldSetPreAuthUrl) {
            $this->sessionUtility
                ->expects($this->once())
                ->method('setInMvc')
                ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', $routeName);
        } else {
            $this->sessionUtility
                ->expects($this->never())
                ->method('setInMvc');
        }

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn($authFailureCode);
        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        $expectedUrl = '/login?state=' . $expectedState;
        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->expects($this->once())
            ->method('assemble')
            ->with(['state' => $expectedState], ['name' => 'login'])
            ->willReturn($expectedUrl);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->expects($this->once())
            ->method('getRouter')
            ->willReturn($router);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals($expectedUrl, $result->getHeaders()->get('Location')->getFieldValue());
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testProcessWhenNoRoute(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $urlHelper = $this->createMock(UrlHelper::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $listener->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessWhenRouteDoesNotRequireAuth(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn(['unauthenticated_route' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn($identity);

        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn(['requires_auth' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->expects($this->once())
            ->method('getMatchedRoute')
            ->willReturn($route);

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequest $request) use ($routeResult) {
                $this->assertSame($routeResult, $request->getAttribute(RouteResult::class));
                $this->assertIsInt($request->getAttribute('secondsUntilSessionExpires'));
                return true;
            }))
            ->willReturn($expectedResponse);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public static function unauthenticatedProcessDataProvider(): array
    {
        return [
            'timeout reason' => [
                'routeName' => 'some/route',
                'routePath' => '/some/path',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => true,
            ],
            'internal system error' => [
                'routeName' => 'some/route',
                'routePath' => '/some/path',
                'authFailureCode' => 500,
                'expectedState' => 'internalSystemError',
                'shouldSetPreAuthUrl' => true,
            ],
            'user delete route' => [
                'routeName' => 'user/delete',
                'routePath' => '/some/path',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
            'user dashboard route' => [
                'routeName' => 'user/dashboard/settings',
                'routePath' => '/user/dashboard/settings',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
        ];
    }

    /**
     * @dataProvider unauthenticatedProcessDataProvider
     */
    public function testProcessWhenUnauthenticated(
        string $routeName,
        string $routePath,
        ?int $authFailureCode,
        string $expectedState,
        bool $shouldSetPreAuthUrl
    ): void {
        $this->authenticationService
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn(null);

        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);
        $route
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($routePath);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->expects($this->exactly(2))
            ->method('getMatchedRoute')
            ->willReturn($route);
        $routeResult
            ->expects($this->once())
            ->method('getMatchedRouteName')
            ->willReturn($routeName);

        if ($shouldSetPreAuthUrl) {
            $this->sessionUtility
                ->expects($this->once())
                ->method('setInMvc')
                ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', $routePath);
        } else {
            $this->sessionUtility
                ->expects($this->never())
                ->method('setInMvc');
        }

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::AUTH_FAILURE_REASON, 'code')
            ->willReturn($authFailureCode);
        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        $expectedUrl = '/login?state=' . $expectedState;
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('login', [], ['state' => $expectedState])
            ->willReturn($expectedUrl);

        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals($expectedUrl, $result->getHeaderLine('Location'));
    }
}
