<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Container\ContainerInterface;

class AuthenticationListenerFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationListener
    {
        return new AuthenticationListener();
    }
}
