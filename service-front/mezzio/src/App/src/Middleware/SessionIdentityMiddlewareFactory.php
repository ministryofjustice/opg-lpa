<?php

declare(strict_types=1);

namespace App\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class SessionIdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): SessionIdentityMiddleware
    {
        // Retrieve the same LpaApplicationService instance that handlers use so
        // that writing the identity into its auth storage is visible to getUserId().
        $lpaApplicationService = $container->get(LpaApplicationService::class);

        /** @var AuthenticationService $authService */
        $authService = $lpaApplicationService->getAuthenticationService();

        return new SessionIdentityMiddleware($authService);
    }
}
