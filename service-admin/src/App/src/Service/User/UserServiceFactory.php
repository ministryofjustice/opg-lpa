<?php

namespace App\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UserServiceFactory
 * @package App\Service\User
 */
class UserServiceFactory
{
    /**
     * @param ContainerInterface $container
     * @return UserService
     */
    public function __invoke(ContainerInterface $container)
    {
        $service = new UserService($container->get(ApiClient::class));

        if ($container->has(LoggerInterface::class)) {
            $service->setLogger($container->get(LoggerInterface::class));
        }

        return $service;
    }
}
