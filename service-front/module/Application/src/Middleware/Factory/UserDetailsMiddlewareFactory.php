<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\UserDetailsMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): UserDetailsMiddleware
    {
        return new UserDetailsMiddleware(
            $container->get(SessionUtility::class),
            $container->get(Details::class),
            $container->get(AuthenticationService::class),
            $container->get('SessionManager'),
            $container->get(LoggerInterface::class),
        );
    }
}
