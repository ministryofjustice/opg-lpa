<?php

namespace Application\DataAccess\Mongo;

use MongoDB\Driver\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ManagerFactory implements FactoryInterface
{
    /**
     * Create MongoDB Manager
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')['db']['mongo']['default'];

        // Split the array out into comma separated values.
        $uri = 'mongodb://' . implode(',', $config['hosts']) . '/' . $config['options']['db'];

        $options = $config['options'];
        if (isset($options['socketTimeoutMS'])) {
            // This connection option only works on the url itself
            $uri .= '?socketTimeoutMS=' . $options['socketTimeoutMS'];
            unset($options['socketTimeoutMS']);
        }

        return new Manager($uri, $options, $config['driverOptions']);
    }
}
