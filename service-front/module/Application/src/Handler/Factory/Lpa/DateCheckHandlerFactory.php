<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\DateCheckHandler;
use Application\Helper\MvcUrlHelper;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DateCheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): DateCheckHandler
    {
        return new DateCheckHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
