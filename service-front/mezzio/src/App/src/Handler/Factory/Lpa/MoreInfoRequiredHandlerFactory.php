<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\MoreInfoRequiredHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class MoreInfoRequiredHandlerFactory
{
    public function __invoke(ContainerInterface $container): MoreInfoRequiredHandler
    {
        return new MoreInfoRequiredHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
