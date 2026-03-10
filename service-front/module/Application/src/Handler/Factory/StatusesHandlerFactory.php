<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\StatusesHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
