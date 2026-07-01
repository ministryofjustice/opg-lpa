<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\UserDetails;
use App\Storage\MezzioSessionStorage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class IdentityTokenRefreshMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): IdentityTokenRefreshMiddleware
    {
        $authService = $container->get(AuthenticationService::class);
        $apiClient   = $container->get(ApiClient::class);

        // Reuse the fully-ported App\Service\UserDetails singleton from the container
        // rather than constructing a legacy Application\Model\Service\User\Details.
        $userService = $container->get(UserDetails::class);

        $middleware = new IdentityTokenRefreshMiddleware(
            $authService,
            $userService,
            $container->get(MezzioSessionStorage::class),
            $apiClient,
        );
        $middleware->setLogger($container->get(LoggerInterface::class));

        return $middleware;
    }
}
