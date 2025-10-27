<?php

namespace App\Service\Cache;

use Psr\Container\ContainerInterface;
use Laminas\Cache\Storage\StorageInterface;

/**
 * Class CacheFactory
 * @package App\Service\Cache
 */
class CacheFactory
{
    /**
     * @param ContainerInterface $container
     * @return StorageInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $stackName = (isset($config['stack']['name']) ? $config['stack']['name'] : 'default');

        return new Cache($config['cache'], $stackName);
    }
}
