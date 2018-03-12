<?php

namespace Application\Model\Service\ApiClient;

use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Http\Client\HttpClient as HttpClientInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

class ClientFactory implements FactoryInterface
{
    /**
     * Create and instance of the API Client
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $serviceLocator->get('HttpClient');

        $baseApiUri = $serviceLocator->get('config')['api_client']['api_uri'];

        $token = null;

        /** @var Container $userDetailsSession */
        $userDetailsSession = $serviceLocator->get('UserDetailsSession');
        $identity = $userDetailsSession->identity;

        if ($identity instanceof UserIdentity) {
            $token = $identity->token();
        }

        return new Client($httpClient, $baseApiUri, $token);
    }
}
