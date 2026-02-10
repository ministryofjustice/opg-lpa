<?php

namespace Application\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Application\Model\Service\Session\ContainerNamespace;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Psr\Container\NotFoundExceptionInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $storage = new SessionStorage(ContainerNamespace::IDENTITY, 'identity');

        /** @var AdapterInterface $adapter */
        $adapter = $container->get('LpaAuthAdapter');

        return new AuthenticationService($storage, $adapter);
    }
}
