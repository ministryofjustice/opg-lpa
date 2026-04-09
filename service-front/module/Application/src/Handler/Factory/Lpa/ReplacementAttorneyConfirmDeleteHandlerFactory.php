<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyConfirmDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): ReplacementAttorneyConfirmDeleteHandler
    {
        return new ReplacementAttorneyConfirmDeleteHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
