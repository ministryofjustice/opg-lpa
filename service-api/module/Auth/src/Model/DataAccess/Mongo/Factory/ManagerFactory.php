<?php

namespace Auth\Model\DataAccess\Mongo\Factory;

use Interop\Container\ContainerInterface;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;

class ManagerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Manager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['db']['mongo']['auth'];

        // Split the array out into comma separated values.
        $uri = 'mongodb://' . implode(',', $config['hosts']) . '/' . $config['options']['db'];

        return new Manager($uri, $config['options'], $config['driverOptions']);
    }
}
