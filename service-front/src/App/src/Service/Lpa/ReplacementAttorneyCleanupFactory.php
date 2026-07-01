<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyCleanupFactory
{
    public function __invoke(ContainerInterface $container): ReplacementAttorneyCleanup
    {
        $service = new ReplacementAttorneyCleanup();
        $service->setLpaApplicationService($container->get(LpaApplicationService::class));

        return $service;
    }
}
