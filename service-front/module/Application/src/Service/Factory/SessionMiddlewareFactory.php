<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionMiddleware;
use Psr\Container\ContainerInterface;

class SessionMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): SessionMiddleware
    {
        return new SessionMiddleware(new PhpSessionPersistence());
    }
}
