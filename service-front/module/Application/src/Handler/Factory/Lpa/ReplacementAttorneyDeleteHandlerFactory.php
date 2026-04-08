<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): ReplacementAttorneyDeleteHandler
    {
        return new ReplacementAttorneyDeleteHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(ReplacementAttorneyCleanup::class),
        );
    }
}
