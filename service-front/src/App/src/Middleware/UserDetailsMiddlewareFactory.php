<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use App\Model\UserDetailsHolder;
use App\Service\UserDetails;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): UserDetailsMiddleware
    {
        $authService = $container->get(AuthenticationService::class);
        $userService = $container->get(UserDetails::class);

        $middleware = new UserDetailsMiddleware($userService, $authService, $container->get(UserDetailsHolder::class));
        $middleware->setLogger($container->get(LoggerInterface::class));

        return $middleware;
    }
}
