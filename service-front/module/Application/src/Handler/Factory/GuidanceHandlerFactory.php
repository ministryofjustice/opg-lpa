<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\GuidanceHandler;
use Application\Model\Service\Guidance\Guidance;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class GuidanceHandlerFactory
{
    public function __invoke(ContainerInterface $container): GuidanceHandler
    {
        return new GuidanceHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(Guidance::class),
        );
    }
}
