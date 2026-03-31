<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\RepeatApplicationHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class RepeatApplicationHandlerFactory
{
    public function __invoke(ContainerInterface $container): RepeatApplicationHandler
    {
        return new RepeatApplicationHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
            $container->get('Metadata'),
        );
    }
}
