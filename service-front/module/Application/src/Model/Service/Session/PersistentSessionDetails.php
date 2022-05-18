<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

use Laminas\Router\RouteMatch;
use Laminas\Session\Container;

class PersistentSessionDetails
{
    /** @var Container */
    private $sessionDetails;

    /** @var RouteMatch|null */
    private $route;

    public function __construct(?RouteMatch $route)
    {
        $this->route = $route;
        $this->sessionDetails = new Container('SessionDetails');
        $this->setRouteDetails();
    }

    private function setRouteDetails(): void
    {
        // breadcrumb so we can determine user's last visited route.
        // Also account for any null values, eg activation links or status checks.
        // Unable to assign to RouteInterface, as RouteMatch does not implement RouteInterface
        $this->sessionDetails->currentRoute = (!is_null($this->route)) ?
            $this->route->getMatchedRouteName() :
            '';

        if ($this->sessionDetails->routeStore !== $this->sessionDetails->previousRoute) {
            $this->sessionDetails->previousRoute = $this->sessionDetails->routeStore;
        }

        $this->sessionDetails->routeStore = $this->sessionDetails->currentRoute;
    }

    public function getCurrentRoute(): string
    {
        return $this->sessionDetails->currentRoute ?? '';
    }

    public function getPreviousRoute(): string
    {
        return $this->sessionDetails->previousRoute ?? 'home';
    }
}
