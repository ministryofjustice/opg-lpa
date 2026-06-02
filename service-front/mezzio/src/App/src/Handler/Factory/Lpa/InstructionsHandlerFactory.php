<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\InstructionsHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class InstructionsHandlerFactory
{
    public function __invoke(ContainerInterface $container): InstructionsHandler
    {
        return new InstructionsHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get('Metadata'),
            $container->get(MvcUrlHelper::class),
        );
    }
}
