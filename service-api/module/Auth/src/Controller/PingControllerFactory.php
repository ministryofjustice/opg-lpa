<?php

namespace Application\Controller;

use Application\Model\Service\DataAccess\Mongo\Factory\DatabaseFactory;
use Application\Model\Service\DataAccess\Mongo\Factory\ManagerFactory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class PingControllerFactory implements FactoryInterface
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
        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class);
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class);

        return new PingController($manager, $database);
    }
}