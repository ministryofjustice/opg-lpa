<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

use Laminas\Router\RouteMatch;

class PersistentSessionDetails
{
    public function __construct(private ?RouteMatch $route, private SessionUtility $sessionUtility)
    {
        $this->setRouteDetails();
    }

    private function setRouteDetails(): void
    {
        // breadcrumb so we can determine user's last visited route.
        // Also account for any null values, eg activation links or status checks.
        // Unable to assign to RouteInterface, as RouteMatch does not implement RouteInterface
        $currentRoute = !is_null($this->route) ? $this->route->getMatchedRouteName() : '';
        $this->sessionUtility->setInMvc('SessionDetails', 'currentRoute', $currentRoute);

        $routeStore = $this->sessionUtility->getFromMvc('SessionDetails', 'routeStore');
        $previousRoute = $this->sessionUtility->getFromMvc('SessionDetails', 'previousRoute');

        if ($routeStore !== $previousRoute) {
            $this->sessionUtility->setInMvc('SessionDetails', 'previousRoute', $routeStore);
        }

        $this->sessionUtility->setInMvc('SessionDetails', 'routeStore', $currentRoute);
    }

    public function getCurrentRoute(): string
    {
        return $this->sessionUtility->getFromMvc('SessionDetails', 'currentRoute') ?? '';
    }

    public function getPreviousRoute(): string
    {
        return $this->sessionUtility->getFromMvc('SessionDetails', 'previousRoute') ?? 'home';
    }
}
