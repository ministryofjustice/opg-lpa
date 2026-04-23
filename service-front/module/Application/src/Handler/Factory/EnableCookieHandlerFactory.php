<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\EnableCookieHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class EnableCookieHandlerFactory
{
    public function __invoke(ContainerInterface $container): EnableCookieHandler
    {
        return new EnableCookieHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
