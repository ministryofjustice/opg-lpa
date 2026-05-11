<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\AuthenticationMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddlewareTest extends TestCase
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

    public function testProcessWhenNoRoute(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $urlHelper = $this->createMock(UrlHelper::class);

        $listener = new AuthenticationMiddleware(
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

        $listener = new AuthenticationMiddleware(
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

        $listener = new AuthenticationMiddleware(
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
                'requestPath' => '/lpa/12345678/view-docs',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => true,
            ],
            'internal system error' => [
                'routeName' => 'some/route',
                'requestPath' => '/lpa/12345678/view-docs',
                'authFailureCode' => 500,
                'expectedState' => 'internalSystemError',
                'shouldSetPreAuthUrl' => true,
            ],
            'user delete route' => [
                'routeName' => 'user/delete',
                'requestPath' => '/user/delete',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
            'user dashboard route' => [
                'routeName' => 'user/dashboard/settings',
                'requestPath' => '/user/dashboard/settings',
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
        string $requestPath,
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

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult
            ->expects($this->once())
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
                ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', $requestPath);
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

        $expectedUrl = '/login/' . $expectedState;
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('login', ['state' => $expectedState])
            ->willReturn($expectedUrl);

        $request = (new ServerRequest(uri: $requestPath))->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $listener = new AuthenticationMiddleware(
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $result = $listener->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals($expectedUrl, $result->getHeaderLine('Location'));
    }
}
