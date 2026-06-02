<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\CertificateProvider;

use App\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
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
