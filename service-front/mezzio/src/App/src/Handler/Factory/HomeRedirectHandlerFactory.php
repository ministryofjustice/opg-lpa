<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\HomeRedirectHandler;
use Psr\Container\ContainerInterface;

class HomeRedirectHandlerFactory
{
    public function __invoke(ContainerInterface $container): HomeRedirectHandler
    {
        return new HomeRedirectHandler(
            $container->get('config'),
        );
    }
}
