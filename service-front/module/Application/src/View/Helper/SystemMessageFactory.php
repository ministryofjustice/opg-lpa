<?php

namespace Application\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SystemMessageFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return SystemMessage
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new SystemMessage($container->get('DynamoDbSystemMessageCache'));
    }
}
