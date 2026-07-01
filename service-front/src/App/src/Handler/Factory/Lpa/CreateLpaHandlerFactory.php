<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\CreateLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class CreateLpaHandlerFactory
{
    public function __invoke(ContainerInterface $container): CreateLpaHandler
    {
        return new CreateLpaHandler(
            $container->get(LpaApplicationService::class),
        );
    }
}
