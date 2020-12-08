<?php

namespace Application\View\Helper;

use Application\Model\Service\Session\SessionManager;
use Laminas\Router\RouteMatch;
use Laminas\View\Helper\AbstractHelper;

class RouteName extends AbstractHelper
{
    /**
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * @var SessionManager
     */
    private $session;

    /**
     * @param SessionManager $session
     * @param RouteMatch|null $routeMatch
     */
    public function __construct(SessionManager $session, ?RouteMatch $routeMatch)
    {
        $this->session = $session;
        $this->routeMatch = $routeMatch;
    }

    public function __invoke()
    {
        $routeName = [];
        $routeName['current'] = ($this->routeMatch) ?
            $this->routeMatch->getMatchedRouteName() :
            '';

        $routeName['previous'] = $this->session->getLastMatchedRouteName();

        return $routeName;
    }
}
