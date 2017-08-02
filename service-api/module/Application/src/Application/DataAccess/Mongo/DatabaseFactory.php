<?php

namespace Application\DataAccess\Mongo;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DatabaseFactory implements FactoryInterface
{
    /**
     * Create MongoDB Database
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Database
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var Manager $manager */
        $manager = $serviceLocator->get(ManagerFactory::class);

        $databaseName = $serviceLocator->get('config')['db']['mongo']['default']['options']['db'];

        return new Database($manager, $databaseName);
    }
}