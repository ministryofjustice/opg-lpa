<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CertificateProviderConfirmDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): CertificateProviderConfirmDeleteHandler
    {
        return new CertificateProviderConfirmDeleteHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
