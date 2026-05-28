<?php

declare(strict_types=1);

namespace App\Service;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use App\Storage\MezzioSessionStorage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LpaApplicationServiceFactory
{
    public function __invoke(ContainerInterface $container): LpaApplicationService
    {
        $config = $container->get('config');

        // Use the shared ApiClient so IdentityTokenRefreshMiddleware's updateToken()
        // call is visible to all API requests made during the same request cycle.
        $apiClient = $container->get(ApiClient::class);

        $authAdapter = new LpaAuthAdapter($apiClient);
        // Use MezzioSessionStorage so the identity persists across requests via the
        // Mezzio session. IdentityTokenRefreshMiddleware calls setSession() on this
        // storage at the start of each request before getUserId() is needed.
        $storage     = $container->get(MezzioSessionStorage::class);
        $authService = new AuthenticationService($storage, $authAdapter);

        $service = new LpaApplicationService($authService, $config);
        $service->setApiClient($apiClient);
        $service->setLogger($container->get(LoggerInterface::class));

        return $service;
    }
}
