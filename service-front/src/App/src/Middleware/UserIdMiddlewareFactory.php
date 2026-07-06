<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserIdMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): UserIdMiddleware
    {
        return new UserIdMiddleware(
            $container->get(LoggerInterface::class),
            $container->get(AuthenticationService::class),
        );
    }
}
