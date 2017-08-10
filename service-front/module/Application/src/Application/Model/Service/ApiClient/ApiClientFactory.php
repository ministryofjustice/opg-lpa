<?php

namespace Application\Model\Service\ApiClient;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiClientFactory implements FactoryInterface
{
    /**
     * Create and instance of the API Client.
     *
     * If the user identity exists, pre-set the userId and token in the client.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')['api_client'];

        $client = new Client($config['api_uri'], $config['auth_uri']);

        $auth = $serviceLocator->get('AuthenticationService');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();

            $client->setUserId($identity->id());
            $client->setToken($identity->token());
        }

        return $client;
    }
}
