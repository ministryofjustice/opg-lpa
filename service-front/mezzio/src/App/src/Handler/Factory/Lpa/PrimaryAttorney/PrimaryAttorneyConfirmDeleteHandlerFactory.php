<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\PrimaryAttorney;

use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
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
