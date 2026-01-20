<?php

namespace Application\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Application\Model\Service\Session\ContainerNamespace;
use Psr\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Authentication\Storage\Session as SessionStorage;

class AuthenticationServiceFactory implements FactoryInterface
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
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $storage = new SessionStorage(ContainerNamespace::USER_DETAILS, 'identity');

        /** @var AdapterInterface $adapter */
        $adapter = $container->get('LpaAuthAdapter');

        return new AuthenticationService($storage, $adapter);
    }
}
