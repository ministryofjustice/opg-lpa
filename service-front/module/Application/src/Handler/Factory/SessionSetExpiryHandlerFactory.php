<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\SessionSetExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Psr\Container\ContainerInterface;

class SessionSetExpiryHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionSetExpiryHandler
    {
        return new SessionSetExpiryHandler(
            $container->get(AuthenticationService::class),
        );
    }
}
