<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\CompleteIndexHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Service\CompleteViewParamsHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CompleteIndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): CompleteIndexHandler
    {
        return new CompleteIndexHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
            $container->get(CompleteViewParamsHelper::class),
        );
    }
}
