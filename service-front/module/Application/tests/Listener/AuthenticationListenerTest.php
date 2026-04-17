<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\EventParameter;
use Application\Listener\AuthenticationListener;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as MVCResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
            ->with(EventParameter::IDENTITY, $identity);

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
                'requestUri' => '/lpa/91155453023/view-docs',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => true,
            ],
            'internal system error' => [
                'routeName' => 'some/route',
                'requestUri' => '/lpa/91155453023/view-docs',
                'authFailureCode' => 500,
                'expectedState' => 'internalSystemError',
                'shouldSetPreAuthUrl' => true,
            ],
            'user delete route' => [
                'routeName' => 'user/delete',
                'requestUri' => '/user/delete',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
            'user dashboard route' => [
                'routeName' => 'user/dashboard/settings',
                'requestUri' => '/user/dashboard/settings',
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
        string $requestUri,
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
                ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url', $requestUri);
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

        $request = $this->createMock(HttpRequest::class);
        $request
            ->expects($this->once())
            ->method('getUriString')
            ->willReturn($requestUri);

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
            ->method('getRequest')
            ->willReturn($request);

        $listener = new AuthenticationListener(
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $this->assertInstanceOf(MVCResponse::class, $result);
        $this->assertEquals($expectedUrl, $result->getHeaders()->get('Location')->getFieldValue());
        $this->assertEquals(302, $result->getStatusCode());
    }
}
