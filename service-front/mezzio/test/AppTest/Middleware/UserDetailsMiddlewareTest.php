<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\RequestAttribute;
use App\Middleware\UserDetailsMiddleware;
use App\Model\UserDetailsHolder;
use App\Authentication\AuthenticationService;
use App\Service\UserDetails as Details;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User as UserDataModel;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsMiddlewareTest extends TestCase
{
    private Details&MockObject $userService;
    private AuthenticationService&MockObject $authenticationService;
    private UserDetailsHolder $userDetailsHolder;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->userService           = $this->createMock(Details::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userDetailsHolder     = new UserDetailsHolder();
        $this->logger                = $this->createMock(LoggerInterface::class);
    }

    private function makeMiddleware(): UserDetailsMiddleware
    {
        $middleware = new UserDetailsMiddleware(
            $this->userService,
            $this->authenticationService,
            $this->userDetailsHolder,
        );
        $middleware->setLogger($this->logger);

        return $middleware;
    }

    public function testProcessWhenNoRouteResult(): void
    {
        // No RouteResult on the request — pass through without fetching user details
        $request         = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $this->userService->expects($this->never())->method('getUserDetails');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $result = $this->makeMiddleware()->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenRouteIsUnauthenticated(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn(['unauthenticated_route' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request          = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $this->userService->expects($this->never())->method('getUserDetails');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $this->assertEquals($expectedResponse, $this->makeMiddleware()->process($request, $handler));
    }

    public function testProcessWhenUserDetailsNotAvailable(): void
    {
        $this->userService->expects($this->once())->method('getUserDetails')->willReturn(false);

        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request          = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($this->callback(function ($req) {
            return $req->getAttribute(RequestAttribute::USER_DETAILS) === null;
        }))->willReturn($expectedResponse);

        $this->assertEquals($expectedResponse, $this->makeMiddleware()->process($request, $handler));
    }

    public function testProcessWhenUserDetailsAvailable(): void
    {
        $userDetails = FixturesData::getUser();

        $this->userService->expects($this->once())->method('getUserDetails')->willReturn($userDetails);

        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request          = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($this->callback(function ($req) use ($userDetails) {
            return $req->getAttribute(RequestAttribute::USER_DETAILS) === $userDetails;
        }))->willReturn($expectedResponse);

        $result = $this->makeMiddleware()->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
        $this->assertSame($userDetails, $this->userDetailsHolder->get());
    }

    public function testProcessWhenIncompleteUserAndRouteAllowsIt(): void
    {
        $userDetails       = FixturesData::getUser();
        $userDetails->name = null;

        $this->userService->expects($this->once())->method('getUserDetails')->willReturn($userDetails);

        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn(['allowIncompleteUser' => true]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request          = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $this->makeMiddleware()->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenIncompleteUserRedirectsToAboutYou(): void
    {
        $userDetails       = FixturesData::getUser();
        $userDetails->name = null;

        $this->userService->expects($this->once())->method('getUserDetails')->willReturn($userDetails);

        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->makeMiddleware()->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/user/about-you/new', $result->getHeaderLine('Location'));
    }

    public function testProcessWhenUserValidationFails(): void
    {
        $userDetails = $this->getMockBuilder(UserDataModel::class)
            ->setConstructorArgs([[
                'id'        => str_repeat('a', 32),
                'createdAt' => '2024-01-01 00:00:00',
                'updatedAt' => '2024-01-01 00:00:00',
            ]])
            ->onlyMethods(['toArray', 'getName'])
            ->getMock();

        $userDetails->method('getName')->willReturn(new Name(['first' => 'Test', 'last' => 'User']));
        $userDetails->method('toArray')->willReturn([
            'id'   => str_repeat('a', 32),
            'name' => 'invalid-string-not-array',
        ]);

        $this->userService->expects($this->once())->method('getUserDetails')->willReturn($userDetails);
        $this->authenticationService->expects($this->once())->method('clearIdentity');

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('clear');
        $session->expects($this->once())->method('regenerate');

        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedRoute')->willReturn($route);

        $request = (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $this->logger->expects($this->once())->method('error')->with(
            'constructing User data from session failed',
            $this->callback(fn($ctx) => isset($ctx['exception'])),
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->makeMiddleware()->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login?state=timeout', $result->getHeaderLine('Location'));
    }
}
