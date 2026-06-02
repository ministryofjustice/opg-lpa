<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\WhenLpaStartsHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class WhenLpaStartsHandlerFactory
{
    public function __invoke(ContainerInterface $container): WhenLpaStartsHandler
    {
        return new WhenLpaStartsHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
