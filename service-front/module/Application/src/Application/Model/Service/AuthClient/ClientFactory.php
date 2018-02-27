<?php

namespace Application\Model\Service\AuthClient;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ClientFactory implements FactoryInterface
{
    /**
     * Create and instance of the API client for the auth service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')['api_client'];

        $client = new Client($config['auth_uri']);

        $auth = $serviceLocator->get('AuthenticationService');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();

            $client->setUserId($identity->id());
            $client->setToken($identity->token());
        }

        return $client;
    }
}
