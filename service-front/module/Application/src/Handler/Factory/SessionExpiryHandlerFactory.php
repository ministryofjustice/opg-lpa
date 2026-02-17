<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\SessionExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Psr\Container\ContainerInterface;

class SessionExpiryHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionExpiryHandler
    {
        return new SessionExpiryHandler(
            $container->get(AuthenticationService::class),
            $container->get(SessionManagerSupport::class),
        );
    }
}
