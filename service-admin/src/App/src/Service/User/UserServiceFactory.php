<?php

namespace App\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use Psr\Container\ContainerInterface;

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
        return new UserService($container->get(ApiClient::class));
    }
}
