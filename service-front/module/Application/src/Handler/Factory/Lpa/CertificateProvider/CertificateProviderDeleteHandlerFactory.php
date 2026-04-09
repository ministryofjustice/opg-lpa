<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class CertificateProviderDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): CertificateProviderDeleteHandler
    {
        return new CertificateProviderDeleteHandler(
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
