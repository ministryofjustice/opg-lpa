<?php

declare(strict_types=1);

namespace App\Service\System;

use App\Service\AddressLookup\OrdnanceSurvey;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\Redis\RedisClient;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Psr\Container\ContainerInterface;

class StatusServiceFactory
{
    public function __invoke(ContainerInterface $container): StatusService
    {
        $config = $container->get('config');
        $getOrNull = static function (string $service) use ($container): mixed {
            try {
                return $container->get($service);
            } catch (\Throwable) {
                return null;
            }
        };

        return new StatusService(
            $container->get(ApiClient::class),
            $getOrNull(DynamoDbClient::class),
            $getOrNull(SaveHandlerInterface::class),
            $getOrNull(MailTransportInterface::class),
            $getOrNull(OrdnanceSurvey::class),
            $getOrNull(RedisClient::class),
            $config,
        );
    }
}
