<?php

declare(strict_types=1);

namespace App\Service\Redis;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RuntimeException;

class RedisClientFactory
{
    public function __invoke(ContainerInterface $container): RedisClient
    {
        $config = $container->get('config')['redis'] ?? throw new RuntimeException('Redis configuration is not available');

        $redisUrl = $config['url'] ?? throw new RuntimeException('Redis URL is not configured');
        $keyTtlSeconds = $config['keyTtlSeconds'] ?? throw new RuntimeException('Redis key TTL is not configured');
        $authToken = $config['authToken'] ?? throw new RuntimeException('Redis auth token is not configured');

        $urlParts = parse_url($redisUrl);

        if (!isset($urlParts['host'])) {
            throw new RuntimeException('Redis hostname could not be parsed from provided URL');
        }
        $host = $urlParts['host'];

        if (($urlParts['scheme'] ?? '') === 'tls') {
            $host = 'tls://' . $host;
        }

        $port = isset($urlParts['port']) ? intval($urlParts['port']) : 6379;

        return new RedisClient(
            $keyTtlSeconds,
            new Redis(),
            $host,
            $port,
            $authToken,
            $container->get(LoggerInterface::class),
        );
    }
}
