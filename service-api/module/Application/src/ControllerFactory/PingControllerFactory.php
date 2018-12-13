<?php

namespace Application\ControllerFactory;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Controller\PingController;
use DynamoQueue\Queue\Client as DynamoQueueClient;
use Interop\Container\ContainerInterface;
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

        /** @var ZendDbAdapter $database */
        $database = $container->get('ZendDbAdapter');

        return new PingController($dynamoQueueClient, $database);
    }
}
