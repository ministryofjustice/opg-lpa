<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Redis\RedisClient;
use Psr\Container\ContainerInterface;
use Redis;

class RedisClientFactory
{
    public function __invoke(ContainerInterface $container): RedisClient
    {
        $config = $container->get('config');

        return new RedisClient(
            $config['redis']['url'],
            $config['redis']['ttlMs'],
            new Redis(),
        );
    }
}
