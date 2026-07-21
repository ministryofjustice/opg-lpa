<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;

class SignOutHandlerFactory
{
    public function __invoke(ContainerInterface $container): SignOutHandler
    {
        $config        = $container->get('config');
        $cognitoConfig = $config['cognito'] ?? [];
        $albConfig     = $config['alb'] ?? [];

        return new SignOutHandler(
            $cognitoConfig['logout_url'] ?? null,
            $albConfig['session_cookie_name'] ?? null,
        );
    }
}
