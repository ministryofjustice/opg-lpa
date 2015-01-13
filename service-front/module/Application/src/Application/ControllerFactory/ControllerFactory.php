<?php

namespace Application\ControllerFactory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ControllerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator 
     *      - an instance of Zend\Mvc\Controller\ControllerManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sm = $serviceLocator->getServiceLocator();
        $router = $sm->get('Router');
        $routerMatch = $router->match($sm->get('Request'));
        $routeName = $routerMatch->getMatchedRouteName();
        $controllerName = $routerMatch->getParam('controllerName');
        return new $controllerName;
    }
}
