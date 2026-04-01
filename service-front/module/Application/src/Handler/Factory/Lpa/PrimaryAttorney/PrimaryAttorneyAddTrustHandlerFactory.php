<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PrimaryAttorney;

use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyAddTrustHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrimaryAttorneyAddTrustHandler
    {
        return new PrimaryAttorneyAddTrustHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Applicant::class),
            $container->get(ReplacementAttorneyCleanup::class),
            $container->get(SessionUtility::class),
        );
    }
}
