<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Form\Error\FormLinkedErrors;
use Application\Service\AccordionService;
use Application\Service\NavigationViewModelHelper;
use Application\Service\SystemMessage;
use Application\View\Twig\AppFunctionsExtension;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class AppFunctionsExtensionFactory
{
    public function __invoke(ContainerInterface $container): AppFunctionsExtension
    {
        return new AppFunctionsExtension(
            $container->get('config'),
            $container->get(FormLinkedErrors::class),
            $container->get(TemplateRendererInterface::class),
            $container->get(SystemMessage::class),
            $container->get(AccordionService::class),
            $container->get(NavigationViewModelHelper::class),
        );
    }
}
