<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\SessionExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Psr\Container\ContainerInterface;

class SessionExpiryHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionExpiryHandler
    {
        return new SessionExpiryHandler(
            $container->get(AuthenticationService::class),
        );
    }
}
