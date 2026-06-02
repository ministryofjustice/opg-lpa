<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\CompleteViewDocsHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Application\Service\CompleteViewParamsHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CompleteViewDocsHandlerFactory
{
    public function __invoke(ContainerInterface $container): CompleteViewDocsHandler
    {
        return new CompleteViewDocsHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaApplicationService::class),
            $container->get(CompleteViewParamsHelper::class),
        );
    }
}
