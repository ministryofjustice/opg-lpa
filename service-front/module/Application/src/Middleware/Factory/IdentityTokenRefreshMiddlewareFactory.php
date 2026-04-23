<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\IdentityTokenRefreshMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Psr\Container\ContainerInterface;

class IdentityTokenRefreshMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): IdentityTokenRefreshMiddleware
    {
        return new IdentityTokenRefreshMiddleware(
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
            $container->get(SessionUtility::class),
        );
    }
}
