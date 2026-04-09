<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\CompleteViewDocsHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
