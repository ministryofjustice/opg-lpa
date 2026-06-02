<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class HowReplacementAttorneysMakeDecisionHandlerFactory
{
    public function __invoke(ContainerInterface $container): HowReplacementAttorneysMakeDecisionHandler
    {
        return new HowReplacementAttorneysMakeDecisionHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
