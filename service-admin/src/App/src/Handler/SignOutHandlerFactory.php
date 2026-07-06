<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;

class SignOutHandlerFactory
{
    public function __invoke(ContainerInterface $container): SignOutHandler
    {
        $cognitoConfig = $container->get('config')['cognito'] ?? [];

        return new SignOutHandler($cognitoConfig['logout_url'] ?? null);
    }
}
