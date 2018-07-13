<?php

namespace Application\ControllerFactory;

use Application\Controller\PingController;
use Application\Model\DataAccess\Mongo\DatabaseFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
use MongoDB\Database;
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
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class . '-default');

        return new PingController($dynamoQueueClient, $database);
    }
}
