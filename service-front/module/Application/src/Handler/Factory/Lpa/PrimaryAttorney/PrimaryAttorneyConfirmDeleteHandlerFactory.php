<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PrimaryAttorney;

use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyConfirmDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrimaryAttorneyConfirmDeleteHandler
    {
        return new PrimaryAttorneyConfirmDeleteHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
