<?php

namespace Application\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\ServiceManager\ServiceLocatorInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AuthenticationService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $storage = new SessionStorage('UserDetails', 'identity');

        /** @var AdapterInterface $adapter */
        $adapter = $serviceLocator->get('LpaAuthAdapter');

        return new AuthenticationService($storage, $adapter);
    }
}
