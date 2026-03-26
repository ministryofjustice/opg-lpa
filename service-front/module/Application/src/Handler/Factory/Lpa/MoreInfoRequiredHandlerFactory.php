<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\MoreInfoRequiredHandler;
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
