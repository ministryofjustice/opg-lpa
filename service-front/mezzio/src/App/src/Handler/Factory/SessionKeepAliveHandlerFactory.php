<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\SessionKeepAliveHandler;
use Psr\Container\ContainerInterface;

class SessionKeepAliveHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionKeepAliveHandler
    {
        return new SessionKeepAliveHandler();
    }
}
