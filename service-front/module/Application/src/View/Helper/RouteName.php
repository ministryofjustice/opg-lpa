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
     * @param RouteMatch|null $routeMatch
     * @param SessionManager $session
     */
    public function __construct(?RouteMatch $routeMatch, SessionManager $session)
    {
        $this->session = $session;
        $this->routeMatch = $routeMatch;
    }

    public function __invoke()
    {
        $routeName = [];

        $routeName[] = ($this->routeMatch) ?
            ['current' => $this->routeMatch->getMatchedRouteName()] :
            ['current' => ''];

        $routeName[] = ['last' => $this->session->getLastMatchedRouteName()];

        return $routeName;
    }
}
