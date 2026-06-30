<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class MetadataFactory
{
    public function __invoke(ContainerInterface $container): Metadata
    {
        $service = new Metadata();
        $service->setLpaApplicationService($container->get(LpaApplicationService::class));
        $service->setLogger($container->get(LoggerInterface::class));

        return $service;
    }
}
