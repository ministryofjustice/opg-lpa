<?php

declare(strict_types=1);

namespace App\Service;

use App\Authentication\AuthenticationService;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Storage\MezzioSessionStorage;
use App\Service\ApiClient\Client as ApiClient;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsFactory
{
    public function __invoke(ContainerInterface $container): UserDetails
    {
        $config = $container->get('config');

        $userService = new UserDetails(
            $container->get(AuthenticationService::class),
            $config,
            $container->get(MailTransportInterface::class),
            $container->get(LoggerInterface::class),
        );
        $userService->setUrlHelper($container->get(UrlHelper::class));
        $userService->setApiClient($container->get(ApiClient::class));
        $userService->setSessionStorage($container->get(MezzioSessionStorage::class));

        return $userService;
    }
}
