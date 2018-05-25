<?php

namespace Application\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SystemMessageFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return SystemMessage
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DynamoDbKeyValueStore $cache */
        $cache = $container->get('Cache');

        return new SystemMessage($cache);
    }
}
