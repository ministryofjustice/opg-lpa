<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\CheckoutIndexHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CheckoutIndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): CheckoutIndexHandler
    {
        return new CheckoutIndexHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get('Communication'),
            $container->get(MvcUrlHelper::class),
        );
    }
}
