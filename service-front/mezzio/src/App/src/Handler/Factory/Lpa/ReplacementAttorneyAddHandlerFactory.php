<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\ReplacementAttorneyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use App\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyAddHandlerFactory
{
    public function __invoke(ContainerInterface $container): ReplacementAttorneyAddHandler
    {
        return new ReplacementAttorneyAddHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(ActorReuseDetailsService::class),
            $container->get(Metadata::class),
            $container->get(ReplacementAttorneyCleanup::class),
        );
    }
}
