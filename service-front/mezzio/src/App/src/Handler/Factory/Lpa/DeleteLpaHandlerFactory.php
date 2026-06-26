<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\DeleteLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class DeleteLpaHandlerFactory
{
    public function __invoke(ContainerInterface $container): DeleteLpaHandler
    {
        return new DeleteLpaHandler(
            $container->get(LpaApplicationService::class),
        );
    }
}
