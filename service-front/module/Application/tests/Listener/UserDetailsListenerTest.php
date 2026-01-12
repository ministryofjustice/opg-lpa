<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\UserDetailsListener;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class UserDetailsListenerTest extends TestCase
{
    private SessionUtility|MockObject $sessionUtility;
    private Details $userService;
    private EventManagerInterface|MockObject $eventManager;

    public function setUp(): void
    {
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->userService = $this->createMock(Details::class);
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
                        && $arg[0] instanceof UserDetailsListener
                        && $arg[1] === 'listen';
                }),
                1
            )
            ->willReturn($expectedFn);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $listener->attach($this->eventManager);
    }

    public function testListenWhenNoRouteMatch(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn(null);

        $this->userService
            ->expects($this->never())
            ->method('getUserDetails');

        $event
            ->expects($this->never())
            ->method('setParam');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
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

        $this->userService
            ->expects($this->never())
            ->method('getUserDetails');

        $event
            ->expects($this->never())
            ->method('setParam');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUserDetailsAvailable(): void
    {
        $userDetails = FixturesData::getUser();

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);

        $event
            ->expects($this->once())
            ->method('setParam')
            ->with('userDetails', $userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUserDetailsNotAvailable(): void
    {
        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn(false);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);

        $event
            ->expects($this->never())
            ->method('setParam');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testProcessWhenNoRoute(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $this->userService
            ->expects($this->never())
            ->method('getUserDetails');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $result = $listener->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenRouteIsUnauthenticated(): void
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

        $this->userService
            ->expects($this->never())
            ->method('getUserDetails');

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenUserDetailsAvailable(): void
    {
        $userDetails = FixturesData::getUser();

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn([]);

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
            ->with($this->callback(function ($req) use ($userDetails) {
                return $req instanceof ServerRequest
                    && $req->getAttribute('userDetails') === $userDetails;
            }))
            ->willReturn($expectedResponse);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenUserDetailsNotAvailable(): void
    {
        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn(false);

        $this->sessionUtility
            ->expects($this->never())
            ->method('setInMvc');

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn([]);

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
            ->with($this->callback(function ($req) {
                return $req instanceof ServerRequest
                    && $req->getAttribute('userDetails') === null;
            }))
            ->willReturn($expectedResponse);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }
}
