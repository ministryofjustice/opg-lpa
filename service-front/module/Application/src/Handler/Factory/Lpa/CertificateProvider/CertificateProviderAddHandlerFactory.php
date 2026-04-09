<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CertificateProviderAddHandlerFactory
{
    public function __invoke(ContainerInterface $container): CertificateProviderAddHandler
    {
        return new CertificateProviderAddHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Metadata::class),
            $container->get(ActorReuseDetailsService::class),
        );
    }
}
