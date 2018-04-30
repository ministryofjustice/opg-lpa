<?php

namespace Application\Controller;

use Application\DataAccess\Mongo\DatabaseFactory;
use Application\DataAccess\Mongo\ManagerFactory;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class PingControllerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DynamoQueueClient $dynamoQueueClient */
        $dynamoQueueClient = $container->get('DynamoQueueClient');
        /** @var Manager $manager */
        $manager = $container->get(ManagerFactory::class);
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class);
        $authPingEndPoint = $container->get('config')['authentication']['ping'];

        return new PingController($dynamoQueueClient, $manager, $database, $authPingEndPoint);
    }
}
