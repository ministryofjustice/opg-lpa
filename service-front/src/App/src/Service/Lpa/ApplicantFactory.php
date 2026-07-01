<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class ApplicantFactory
{
    public function __invoke(ContainerInterface $container): Applicant
    {
        $service = new Applicant();
        $service->setLpaApplicationService($container->get(LpaApplicationService::class));

        return $service;
    }
}
