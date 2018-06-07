<?php

namespace Application\Model\DataAccess\Mongo;

use Interop\Container\ContainerInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Exception;

class DatabaseFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Database
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (strpos($requestedName, DatabaseFactory::class . '-') !== 0) {
            throw new Exception(sprintf('To retrieve %s a requestName in the format %s-[configKey] must be used', get_class($this), get_class($this)));
        }

        $configKey = str_replace(DatabaseFactory::class . '-', '', $requestedName);

        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class . '-' . $configKey);

        $databaseName = $container->get('config')['db']['mongo'][$configKey]['options']['db'];

        return new Database($manager, $databaseName);
    }
}
