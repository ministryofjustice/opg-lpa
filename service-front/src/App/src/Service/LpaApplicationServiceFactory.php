<?php

declare(strict_types=1);

namespace App\Service;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LpaApplicationServiceFactory
{
    public function __invoke(ContainerInterface $container): LpaApplicationService
    {
        $config    = $container->get('config');
        $apiClient = $container->get(ApiClient::class);

        $service = new LpaApplicationService(
            $container->get(AuthenticationService::class),
            $config,
        );
        $service->setApiClient($apiClient);
        $service->setLogger($container->get(LoggerInterface::class));

        return $service;
    }
}
