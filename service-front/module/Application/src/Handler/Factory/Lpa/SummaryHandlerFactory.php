<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\SummaryHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class SummaryHandlerFactory
{
    public function __invoke(ContainerInterface $container): SummaryHandler
    {
        return new SummaryHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}
