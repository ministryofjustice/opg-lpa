<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\AccessibilityHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class AccessibilityHandlerFactory
{
    public function __invoke(ContainerInterface $container): AccessibilityHandler
    {
        return new AccessibilityHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
