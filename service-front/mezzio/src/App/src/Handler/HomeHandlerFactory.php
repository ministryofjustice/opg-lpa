<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class HomeHandlerFactory
{
    public function __invoke(ContainerInterface $container): HomeHandler
    {
        return new HomeHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get('config'),
        );
    }
}
