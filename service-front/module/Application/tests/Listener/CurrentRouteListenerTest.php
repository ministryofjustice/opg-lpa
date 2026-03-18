<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\CurrentRouteListener;
use Application\Listener\EventParameter;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use PHPUnit\Framework\TestCase;

class CurrentRouteListenerTest extends TestCase
{
    private CurrentRouteListener $listener;

    protected function setUp(): void
    {
        $this->listener = new CurrentRouteListener();
    }

    public function testAttachRegistersDispatchListener(): void
    {
        $events = $this->createMock(EventManagerInterface::class);
        $events->expects($this->once())
            ->method('attach')
            ->with(MvcEvent::EVENT_DISPATCH, [$this->listener, 'listen'], 1);

        $this->listener->attach($events);
    }

    public function testListenDoesNothingWhenNoRouteMatch(): void
    {
        $event = new MvcEvent();

        $this->listener->listen($event);

        $this->assertNull($event->getParam(EventParameter::CURRENT_ROUTE));
    }

    public function testListenDoesNothingWhenRouteNameIsNull(): void
    {
        $routeMatch = new RouteMatch([]);
        // Do not call setMatchedRouteName — name stays null

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->listener->listen($event);

        $this->assertNull($event->getParam(EventParameter::CURRENT_ROUTE));
    }

    public function testListenSetsCurrentRouteOnEvent(): void
    {
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('user/dashboard');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->listener->listen($event);

        $this->assertSame('user/dashboard', $event->getParam(EventParameter::CURRENT_ROUTE));
    }

    public function testListenSetsCurrentRouteForLpaRoutes(): void
    {
        $routeMatch = new RouteMatch(['lpa-id' => '123']);
        $routeMatch->setMatchedRouteName('lpa/form-type');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);

        $this->listener->listen($event);

        $this->assertSame('lpa/form-type', $event->getParam(EventParameter::CURRENT_ROUTE));
    }
}
