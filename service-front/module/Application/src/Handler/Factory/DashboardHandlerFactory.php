<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\DashboardHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DashboardHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DashboardHandler
    {
        return new DashboardHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
        );
    }
}
