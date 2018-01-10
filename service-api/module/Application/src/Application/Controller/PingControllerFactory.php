<?php

namespace Application\Controller;

use Application\DataAccess\Mongo\DatabaseFactory;
use Application\DataAccess\Mongo\ManagerFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PingControllerFactory implements FactoryInterface
{
    /**
     * Create ping controller
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return PingController
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var ControllerManager $serviceLocator */
        $serviceLocator = $serviceLocator->getServiceLocator();

        /** @var DynamoQueueClient $dynamoQueueClient */
        $dynamoQueueClient = $serviceLocator->get('DynamoQueueClient');
        /** @var Manager $manager */
        $manager = $serviceLocator->get(ManagerFactory::class);
        /** @var Database $database */
        $database = $serviceLocator->get(DatabaseFactory::class);
        $authPingEndPoint = $serviceLocator->get('config')['authentication']['ping'];

        return new PingController($dynamoQueueClient, $manager, $database, $authPingEndPoint);
    }
}
