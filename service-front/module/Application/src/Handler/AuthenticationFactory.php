<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Psr\Container\ContainerInterface;

class AuthenticationFactory
{
    public function __invoke(ContainerInterface $container): Authentication
    {
        return new Authentication(
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
            $container->get(SessionManagerSupport::class),
            $container->get('config'),
        );
    }
}
