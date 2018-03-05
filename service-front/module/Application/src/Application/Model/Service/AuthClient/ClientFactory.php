<?php

namespace Application\Model\Service\AuthClient;

use Application\Model\Service\Authentication\AuthenticationService;
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
        $httpClient = $serviceLocator->get('HttpClient');

        $baseAuthUri = $serviceLocator->get('config')['api_client']['auth_uri'];

        $token = null;

        /** @var AuthenticationService $authenticationService */
        $authenticationService = $serviceLocator->get('AuthenticationService');

        if ($authenticationService->hasIdentity()) {
            $identity = $authenticationService->getIdentity();
            $token = $identity->token();
        }

        return new Client($httpClient, $baseAuthUri, $token);
    }
}
