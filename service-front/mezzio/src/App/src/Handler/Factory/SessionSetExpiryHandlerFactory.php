<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\SessionSetExpiryHandler;
use App\Authentication\AuthenticationService;
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
