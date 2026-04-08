<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CertificateProviderEditHandlerFactory
{
    public function __invoke(ContainerInterface $container): CertificateProviderEditHandler
    {
        return new CertificateProviderEditHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
