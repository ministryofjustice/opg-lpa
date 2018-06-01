<?php

namespace Auth\Model\DataAccess\Mongo\Factory;

use Interop\Container\ContainerInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;

class DatabaseFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Database
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class);

        $databaseName = $container->get('config')['db']['mongo']['auth']['options']['db'];

        return new Database($manager, $databaseName);
    }
}
