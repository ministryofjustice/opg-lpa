<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\CheckoutPayResponseHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CheckoutPayResponseHandlerFactory
{
    public function __invoke(ContainerInterface $container): CheckoutPayResponseHandler
    {
        return new CheckoutPayResponseHandler(
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get('Communication'),
            $container->get('GovPayClient'),
            $container->get(MvcUrlHelper::class),
            $container->get(TemplateRendererInterface::class),
        );
    }
}
