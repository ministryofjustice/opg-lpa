<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\PrivacyHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PrivacyHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrivacyHandler
    {
        return new PrivacyHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
