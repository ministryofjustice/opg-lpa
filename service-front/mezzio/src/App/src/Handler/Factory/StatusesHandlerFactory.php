<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\StatusesHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class StatusesHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): StatusesHandler
    {
        return new StatusesHandler(
            $container->get(LpaApplicationService::class),
        );
    }
}
