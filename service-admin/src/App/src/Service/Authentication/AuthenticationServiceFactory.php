<?php

namespace App\Service\Authentication;

use App\Service\ApiClient\Client as ApiClient;
use Psr\Container\ContainerInterface;

/**
 * Class AuthenticationServiceFactory
 * @package App\Service\Authentication
 */
class AuthenticationServiceFactory
{
    /**
     * @param ContainerInterface $container
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $container)
    {
        return new AuthenticationService($container->get(ApiClient::class));
    }
}
