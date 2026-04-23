<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\SessionBootstrapMiddleware;
use Application\Model\Service\Session\SessionManagerSupport;
use Psr\Container\ContainerInterface;

class SessionBootstrapMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): SessionBootstrapMiddleware
    {
        return new SessionBootstrapMiddleware(
            $container->get('SessionManager'),
            $container->get(SessionManagerSupport::class),
        );
    }
}
