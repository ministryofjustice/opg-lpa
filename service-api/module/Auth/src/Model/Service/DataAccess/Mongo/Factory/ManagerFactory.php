<?php

namespace Auth\Model\Service\DataAccess\Mongo\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ManagerFactory implements FactoryInterface
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
        $config = $container->get('config')['db']['mongo']['default'];

        // Split the array out into comma separated values.
        $uri = 'mongodb://' . implode(',', $config['hosts']) . '/' . $config['options']['db'];

        return new Manager($uri, $config['options'], $config['driverOptions']);
    }
}
