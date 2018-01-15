<?php

namespace Application\Controller\Version2;

use Application\Model\Rest\Applications\Resource;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApplicationControllerFactory implements FactoryInterface
{
    /**
     * Create application controller
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApplicationController
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var ControllerManager $serviceLocator */
        $serviceLocator = $serviceLocator->getServiceLocator();

        /** @var Resource $applicationsResource */
        $applicationsResource = $serviceLocator->get('resource-applications');

        return new ApplicationController($applicationsResource);
    }
}
