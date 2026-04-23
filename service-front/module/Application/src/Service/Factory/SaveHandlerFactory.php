<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Session\FilteringSaveHandler;
use Application\Model\Service\Session\WritePolicy;
use Application\Model\Service\Redis\RedisClient;
use Psr\Container\ContainerInterface;

class SaveHandlerFactory
{
    public function __invoke(ContainerInterface $container): FilteringSaveHandler
    {
        $redisClient = $container->get(RedisClient::class);
        $policy = $container->has(WritePolicy::class) ? $container->get(WritePolicy::class) : null;

        $filter = static function () use ($policy) {
            return $policy === null ? empty($_SERVER['HTTP_X_SESSIONREADONLY']) : $policy->allowsWrite();
        };

        return new FilteringSaveHandler($redisClient, [$filter]);
    }
}
