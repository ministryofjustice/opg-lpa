<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\DonorIndexHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DonorIndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): DonorIndexHandler
    {
        return new DonorIndexHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
