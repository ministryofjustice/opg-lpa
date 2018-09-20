<?php

namespace Application\ControllerFactory;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Controller\PingController;
use Application\Model\DataAccess\Mongo\DatabaseFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
use MongoDB\Database as MongoDatabase;
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

        /** @var MongoDatabase $database */
        $mongo = $container->get(DatabaseFactory::class . '-default');

        /** @var ZendDbAdapter $database */
        $database = $container->get('ZendDbAdapter');

        return new PingController($dynamoQueueClient, $database, $mongo);
    }
}
