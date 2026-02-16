<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\LogoutHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Psr\Container\ContainerInterface;

class LogoutHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogoutHandler
    {
        return new LogoutHandler(
            $container->get(AuthenticationService::class),
            $container->get(SessionManagerSupport::class),
            $container->get('config'),
        );
    }
}
