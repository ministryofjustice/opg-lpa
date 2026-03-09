<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
