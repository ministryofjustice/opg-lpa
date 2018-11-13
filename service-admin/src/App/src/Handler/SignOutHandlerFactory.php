<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SignOutHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container) : RequestHandlerInterface
    {
        $config = $container->get('config');

        return new SignOutHandler($config['jwt']);
    }
}
