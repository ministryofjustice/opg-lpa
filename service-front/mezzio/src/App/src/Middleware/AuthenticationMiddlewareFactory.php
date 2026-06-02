<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class AuthenticationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationMiddleware
    {
        $lpaApplicationService = $container->get(LpaApplicationService::class);
        $authService           = $lpaApplicationService->getAuthenticationService();

        return new AuthenticationMiddleware(
            $authService,
            $container->get(UrlHelper::class),
        );
    }
}
