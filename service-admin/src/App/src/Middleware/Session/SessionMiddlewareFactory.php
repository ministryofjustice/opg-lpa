<?php

namespace App\Middleware\Session;

use Psr\Container\ContainerInterface;

/**
 * Class SessionMiddlewareFactory
 * @package App\Middleware\Session
 */
class SessionMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return SessionMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        return new SessionMiddleware($config['jwt']);
    }
}
