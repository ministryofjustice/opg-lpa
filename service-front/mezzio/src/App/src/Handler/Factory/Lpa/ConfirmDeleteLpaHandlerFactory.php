<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\ConfirmDeleteLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ConfirmDeleteLpaHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConfirmDeleteLpaHandler
    {
        return new ConfirmDeleteLpaHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
        );
    }
}
