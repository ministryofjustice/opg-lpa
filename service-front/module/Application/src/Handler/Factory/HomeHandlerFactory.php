<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\HomeHandler;
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
