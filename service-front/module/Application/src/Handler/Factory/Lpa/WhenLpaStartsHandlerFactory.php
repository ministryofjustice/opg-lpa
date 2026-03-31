<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\WhenLpaStartsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
