<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\PrimaryAttorneyHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrimaryAttorneyHandler
    {
        return new PrimaryAttorneyHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
