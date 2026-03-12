<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\SessionKeepAliveHandler;
use Laminas\Session\SessionManager;
use Psr\Container\ContainerInterface;

class SessionKeepAliveHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionKeepAliveHandler
    {
        return new SessionKeepAliveHandler(
            $container->get(SessionManager::class),
        );
    }
}
