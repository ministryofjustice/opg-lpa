<?php

namespace Application\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SystemMessageFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return SystemMessage
     */
    public function __invoke(ContainerInterface|\Psr\Container\ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new SystemMessage($container->get('DynamoDbSystemMessageCache'));
    }
}
