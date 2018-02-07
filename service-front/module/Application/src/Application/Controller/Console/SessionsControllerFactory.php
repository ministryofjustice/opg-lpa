<?php

namespace Application\Controller\Console;

use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\System\DynamoCronLock;
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
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $serviceLocator->get('DynamoCronLock');
        /** @var SessionManager $sessionManager */
        $sessionManager = $serviceLocator->get('SessionManager');

        return new SessionsController($dynamoCronLock, $sessionManager);
    }
}