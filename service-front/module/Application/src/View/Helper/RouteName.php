<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class RouteName extends AbstractHelper
{
    public function __invoke()
    {
        $routeName = '';
        
        $routeMatch = $this->view->getHelperPluginManager()
            ->getServiceLocator()
            ->get('Application')
            ->getMvcEvent()
            ->getRouteMatch();
        
        if ($routeMatch) {
            $routeName = $routeMatch->getMatchedRouteName();
        }
        
        return $routeName;
    }
}