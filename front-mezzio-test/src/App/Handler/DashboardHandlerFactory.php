<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DashboardHandlerFactory
{
    public function __invoke(ContainerInterface $container) : DashboardHandler
    {
        return new DashboardHandler($container->get(TemplateRendererInterface::class));
    }
}
