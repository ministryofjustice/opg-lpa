<?php

namespace Application\DataAccess\Mongo;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\ServiceLocatorInterface;

class DatabaseFactory implements IDatabaseFactory
{
    /**
     * @var string
     */
    private $databaseName;

    public function __construct($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * Create MongoDB Database
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Database
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var Manager $manager */
        $manager = $serviceLocator->get(IManagerFactory::class);
        $database = new Database($manager, $this->databaseName);
        return $database;
    }
}