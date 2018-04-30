<?php

namespace Application\Model\Service\AuthClient;

use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Http\Client\HttpClient as HttpClientInterface;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Session\Container;

class ClientFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var HttpClientInterface $httpClient */
        $httpClient = $container->get('HttpClient');

        $baseAuthUri = $container->get('config')['api_client']['auth_uri'];

        $token = null;

        /** @var Container $userDetailsSession */
        $userDetailsSession = $container->get('UserDetailsSession');
        $identity = $userDetailsSession->identity;

        if ($identity instanceof UserIdentity) {
            $token = $identity->token();
        }

        return new Client($httpClient, $baseAuthUri, $token);
    }
}
