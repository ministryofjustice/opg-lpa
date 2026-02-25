<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\UserDetailsListener;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use MakeShared\DataModel\Common\Name;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response as MVCResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\Session\SessionManager;
use MakeShared\DataModel\User\User as UserDataModel;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsListenerTest extends TestCase
{
    private SessionUtility|MockObject $sessionUtility;
    private Details|MockObject $userService;
    private AuthenticationService|MockObject $authenticationService;
    private SessionManager|MockObject $sessionManager;
    private EventManagerInterface|MockObject $eventManager;
    private LoggerInterface|MockObject $logger;

    public function setUp(): void
    {
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->userService = $this->createMock(Details::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
            $this->logger,
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
            $this->logger,
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
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
            ->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['unauthenticated_route', false, false],
                ['allowIncompleteUser', false, false],
            ]);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
            $this->logger,
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
            ->expects($this->once())
            ->method('getParam')
            ->with('unauthenticated_route', false)
            ->willReturn(false);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenIncompleteUserAndRouteDoesNotAllowIt(): void
    {
        $userDetails = FixturesData::getUser();
        $userDetails->name = null;

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->expects($this->once())
            ->method('assemble')
            ->with(['new' => 'new'], ['name' => 'user/about-you'])
            ->willReturn('/user/about-you/new');

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['unauthenticated_route', false, false],
                ['allowIncompleteUser', false, false],
            ]);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->expects($this->once())
            ->method('getRouter')
            ->willReturn($router);

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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/user/about-you/new', $result->getHeaders()->get('Location')->getFieldValue());
    }

    public function testListenWhenUserValidationFails(): void
    {
        // Create a partial mock of User that overrides toArray() to return invalid data
        // This simulates data corruption that will cause User reconstruction to fail
        $userDetails = $this->getMockBuilder(UserDataModel::class)
            ->setConstructorArgs([[
                'id' => str_repeat('a', 32),
                'createdAt' => '2024-01-01 00:00:00',
                'updatedAt' => '2024-01-01 00:00:00',
            ]])
            ->onlyMethods(['toArray'])
            ->getMock();


        $userDetails->method('toArray')->willReturn([
            'id' => str_repeat('a', 32),
            'name' => 'invalid-string-not-array',
        ]);

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->expects($this->once())
            ->method('assemble')
            ->with(['state' => 'timeout'], ['name' => 'login'])
            ->willReturn('/login?state=timeout');

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch
            ->method('getParam')
            ->willReturnMap([
                ['unauthenticated_route', false, false],
                ['allowIncompleteUser', false, true],
            ]);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouteMatch')
            ->willReturn($routeMatch);
        $event
            ->method('getRouter')
            ->willReturn($router);

        $event
            ->expects($this->once())
            ->method('setParam')
            ->with('userDetails', $userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'constructing User data from session failed',
                $this->callback(fn($context) => isset($context['exception']))
            );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login?state=timeout', $result->getHeaders()->get('Location')->getFieldValue());
    }

    public function testProcessWhenNoRoute(): void
    {
        $userDetails = FixturesData::getUser();

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        // Now we DO fetch user details in MVC hybrid mode
        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
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

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
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

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
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

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenIncompleteUserAndRouteAllowsIt(): void
    {
        $userDetails = FixturesData::getUser();
        $userDetails->name = null;

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
            ->willReturn(['allowIncompleteUser' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        // With allowIncompleteUser => true, user should be allowed through
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $result = $listener->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenUserValidationFails(): void
    {
        // Create a partial mock of User that overrides toArray() to return invalid data
        // User needs a valid name to pass the incomplete user check
        $userDetails = $this->getMockBuilder(UserDataModel::class)
            ->setConstructorArgs([[
                'id' => str_repeat('a', 32),
                'createdAt' => '2024-01-01 00:00:00',
                'updatedAt' => '2024-01-01 00:00:00',
            ]])
            ->onlyMethods(['toArray', 'getName'])
            ->getMock();

        // Return a valid Name object so we pass the incomplete user check
        $userDetails->method('getName')->willReturn(new Name(['first' => 'Test', 'last' => 'User']));

        // But return invalid data for toArray() to trigger validation failure
        $userDetails->method('toArray')->willReturn([
            'id' => str_repeat('a', 32),
            'name' => 'invalid-string-not-array',
        ]);

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $route = $this->createMock(Route::class);
        $route
            ->method('getOptions')
            ->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->method('getMatchedRoute')
            ->willReturn($route);

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $listener = new UserDetailsListener(
            $this->sessionUtility,
            $this->userService,
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'constructing User data from session failed',
                $this->callback(fn($context) => isset($context['exception']))
            );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login?state=timeout', $result->getHeaderLine('Location'));
    }

    public function testProcessWhenNoRouteMvcHybridMode(): void
    {
        $userDetails = FixturesData::getUser();

        $request = new ServerRequest();  // No RouteResult attribute = MVC hybrid mode
        $expectedResponse = new PSR7Response();

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn($userDetails);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $result = $listener->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenNoRouteMvcHybridModeUserDetailsFalse(): void
    {
        $request = new ServerRequest();  // No RouteResult attribute = MVC hybrid mode
        $expectedResponse = new PSR7Response();

        $this->userService
            ->expects($this->once())
            ->method('getUserDetails')
            ->willReturn(false);

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
            $this->authenticationService,
            $this->sessionManager,
            $this->logger,
        );

        $result = $listener->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }
}
