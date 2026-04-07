<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class WhenReplacementAttorneyStepInHandlerFactory
{
    public function __invoke(ContainerInterface $container): WhenReplacementAttorneyStepInHandler
    {
        return new WhenReplacementAttorneyStepInHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(ReplacementAttorneyCleanup::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
