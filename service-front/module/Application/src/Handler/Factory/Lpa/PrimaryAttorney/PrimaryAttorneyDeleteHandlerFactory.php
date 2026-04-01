<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa\PrimaryAttorney;

use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyDeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container): PrimaryAttorneyDeleteHandler
    {
        return new PrimaryAttorneyDeleteHandler(
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get(Applicant::class),
            $container->get(ReplacementAttorneyCleanup::class),
        );
    }
}
