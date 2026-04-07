<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Sets the current route name as an event parameter on every dispatch so that
 * downstream listeners and view helpers can read it without having to inspect
 * the RouteMatch themselves.
 *
 * This listener runs on MVC routes only; its PSR-7 equivalent is
 * RouteMatchMiddleware, which sets the same value as RequestAttribute::CURRENT_ROUTE_NAME
 * on the PSR-7 request.
 */
class CurrentRouteListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'listen'],
            $priority
        );
    }

    public function listen(MvcEvent $event): void
    {
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch === null) {
            return;
        }

        $currentRoute = $routeMatch->getMatchedRouteName();

        if ($currentRoute !== null) {
            $event->setParam(EventParameter::CURRENT_ROUTE_NAME, $currentRoute);
        }
    }
}
