<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\Api\Client\Client as ApiClient;
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
     * @return ApiClient
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $client = new ApiClient();

        $config = $serviceLocator->get('config')['api_client'];
        $client->setApiBaseUri($config['api_uri']);
        $client->setAuthBaseUri($config['auth_uri']);

        $auth = $serviceLocator->get('AuthenticationService');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            $client->setUserId($identity->id());
            $client->setToken($identity->token());
        }

        return $client;
    }
}
