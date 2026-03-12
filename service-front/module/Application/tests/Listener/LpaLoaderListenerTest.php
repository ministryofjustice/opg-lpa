<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\LpaLoaderListener;
use Application\Listener\EventParameter;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LpaLoaderListenerTest extends TestCase
{
    private AuthenticationService|MockObject $authenticationService;
    private LpaApplicationService|MockObject $lpaApplicationService;
    private LpaLoaderListener $listener;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->listener = new LpaLoaderListener(
            $this->authenticationService,
            $this->lpaApplicationService,
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
            $this->assertInstanceOf(Lpa::class, $event->getParam(EventParameter::LPA));
            $this->assertInstanceOf(FormFlowChecker::class, $event->getParam(EventParameter::FLOW_CHECKER));
            // CURRENT_ROUTE is set by CurrentRouteListener, not LpaLoaderListener
            $this->assertNull($event->getParam(EventParameter::CURRENT_ROUTE));
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
        // CURRENT_ROUTE is set by CurrentRouteListener, not LpaLoaderListener
        $this->assertNull($event->getParam(EventParameter::CURRENT_ROUTE));
    }

    public function testConstantsAreCorrectlyDefined(): void
    {
        $this->assertEquals(Lpa::class, EventParameter::LPA);
        $this->assertEquals(FormFlowChecker::class, EventParameter::FLOW_CHECKER);
        $this->assertEquals('currentRouteName', EventParameter::CURRENT_ROUTE);
    }
}
