<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\AuthenticationMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class AuthenticationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationMiddleware
    {
        return new AuthenticationMiddleware(
            $container->get(SessionUtility::class),
            $container->get(AuthenticationService::class),
            $container->get(UrlHelper::class),
        );
    }
}
