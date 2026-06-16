<?php

declare(strict_types=1);

namespace App\Authentication;

use App\Authentication\Adapter\LpaAuthAdapter;
use App\Service\ApiClient\Client as ApiClient;
use App\Storage\MezzioSessionStorage;
use Psr\Container\ContainerInterface;

class AuthenticationServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationService
    {
        $service = new AuthenticationService(new LpaAuthAdapter($container->get(ApiClient::class)));
        $service->setStorage($container->get(MezzioSessionStorage::class));

        return $service;
    }
}
