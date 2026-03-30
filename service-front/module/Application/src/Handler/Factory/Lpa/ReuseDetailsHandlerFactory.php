<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\ReuseDetailsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ReuseDetailsHandlerFactory
{
    public function __invoke(ContainerInterface $container): ReuseDetailsHandler
    {
        return new ReuseDetailsHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(MvcUrlHelper::class),
            $container->get(ActorReuseDetailsService::class),
        );
    }
}
