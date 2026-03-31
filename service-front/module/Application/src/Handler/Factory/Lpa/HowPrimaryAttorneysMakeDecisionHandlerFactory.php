<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class HowPrimaryAttorneysMakeDecisionHandlerFactory
{
    public function __invoke(ContainerInterface $container): HowPrimaryAttorneysMakeDecisionHandler
    {
        return new HowPrimaryAttorneysMakeDecisionHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get('ApplicantService'),
            $container->get(ReplacementAttorneyCleanup::class),
        );
    }
}
