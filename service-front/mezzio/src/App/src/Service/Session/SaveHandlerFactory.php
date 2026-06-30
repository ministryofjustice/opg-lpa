<?php

declare(strict_types=1);

namespace App\Service\Session;

use App\Service\Redis\RedisClient;
use Psr\Container\ContainerInterface;

class SaveHandlerFactory
{
    public function __invoke(ContainerInterface $container): FilteringSaveHandler
    {
        $redisClient = $container->get(RedisClient::class);

        return new FilteringSaveHandler($redisClient, [
            static fn() => empty($_SERVER['HTTP_X_SESSIONREADONLY']),
        ]);
    }
}
