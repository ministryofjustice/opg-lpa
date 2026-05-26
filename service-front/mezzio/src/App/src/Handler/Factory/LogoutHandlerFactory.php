<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\LogoutHandler;
use Psr\Container\ContainerInterface;

class LogoutHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogoutHandler
    {
        return new LogoutHandler(
            $container->get('config'),
        );
    }
}
