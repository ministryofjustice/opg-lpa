<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa\CertificateProvider;

use App\Handler\Lpa\CertificateProvider\CertificateProviderHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CertificateProviderHandlerFactory
{
    public function __invoke(ContainerInterface $container): CertificateProviderHandler
    {
        return new CertificateProviderHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Metadata::class),
        );
    }
}
