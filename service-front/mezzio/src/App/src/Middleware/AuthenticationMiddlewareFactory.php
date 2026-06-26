<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Authentication\AuthenticationService;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class AuthenticationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            $container->get(AuthenticationService::class),
            $container->get(UrlHelper::class),
        );
    }
}
