<?php

declare(strict_types=1);

namespace App\Service\Redis;

use Psr\Container\ContainerInterface;
use Redis;

class RedisClientFactory
{
    public function __invoke(ContainerInterface $container): RedisClient
    {
        $config   = $container->get('config');
        $redisUrl = $config['redis']['url'] ?? (getenv('OPG_LPA_COMMON_REDIS_CACHE_URL') ?: '');
        $ttlMs    = $config['redis']['ttlMs'] ?? (int)(getenv('OPG_LPA_COMMON_REDIS_CACHE_TTL_MS') ?: 604800000);

        return new RedisClient($redisUrl, $ttlMs, new Redis());
    }
}
