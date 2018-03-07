<?php

namespace Application\Model\Service\ApiClient;

use Application\Model\Service\Authentication\AuthenticationService;
use Http\Client\HttpClient as HttpClientInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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

        /** @var AuthenticationService $authenticationService */
        $authenticationService = $serviceLocator->get('AuthenticationService');

        if ($authenticationService->hasIdentity()) {
            $identity = $authenticationService->getIdentity();
            $token = $identity->token();
        }

        $client = new Client($httpClient, $baseApiUri, $token);

//TODO - TO BE REMOVED
        /** @var \Application\Model\Service\User\Details $userService */
        $userService = $serviceLocator->get('UserService');
        $client->setUserService($userService);

        if ($authenticationService->hasIdentity()) {
            $identity = $authenticationService->getIdentity();
            $client->setUserId($identity->id());
        }
//TODO - END TO BE REMOVED

        return $client;
    }
}
