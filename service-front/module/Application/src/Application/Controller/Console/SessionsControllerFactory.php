<?php

namespace Application\Controller\Console;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SessionsControllerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var AccountCleanupService $accountCleanupService */
        $accountCleanupService = $container->get('AccountCleanupService');
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $container->get('DynamoCronLock');

        return new SessionsController($accountCleanupService, $dynamoCronLock, $container->get('config'));
    }
}