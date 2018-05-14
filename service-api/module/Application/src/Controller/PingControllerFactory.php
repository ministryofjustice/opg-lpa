<?php

namespace Application\Controller;

use Application\Model\DataAccess\Mongo\DatabaseFactory;
use Application\Model\DataAccess\Mongo\ManagerFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;

class PingControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return PingController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DynamoQueueClient $dynamoQueueClient */
        $dynamoQueueClient = $container->get('DynamoQueueClient');
        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class);
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class);

        return new PingController($dynamoQueueClient, $manager, $database);
    }
}
