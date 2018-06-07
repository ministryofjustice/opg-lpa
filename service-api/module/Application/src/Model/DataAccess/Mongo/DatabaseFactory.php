<?php

namespace Application\Model\DataAccess\Mongo;

use Interop\Container\ContainerInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;

class DatabaseFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $configKey;

    /**
     * @param string $configKey
     */
    public function __construct($configKey = 'default')
    {
        $this->configKey = $configKey;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Database
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class . '-' . $this->configKey);

        $databaseName = $container->get('config')['db']['mongo'][$this->configKey]['options']['db'];

        return new Database($manager, $databaseName);
    }
}
