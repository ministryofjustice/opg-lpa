<?php

namespace Application\View\Helper;

use Zend\Router\RouteMatch;
use Zend\View\Helper\AbstractHelper;

class RouteName extends AbstractHelper
{
    /**
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * @param RouteMatch $routeMatch
     */
    public function __construct(RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    public function __invoke()
    {
        $routeName = '';

        if ($this->routeMatch) {
            $routeName = $this->routeMatch->getMatchedRouteName();
        }

        return $routeName;
    }
}