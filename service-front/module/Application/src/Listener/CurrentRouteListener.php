<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Sets the current route name as an event parameter on every dispatch
 * so that downstream listeners and view helpers can access it when the
 * route is handled by a traditional MVC controller
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
            $event->setParam(EventParameter::CURRENT_ROUTE, $currentRoute);
        }
    }
}
