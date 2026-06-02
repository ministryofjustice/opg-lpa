<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\CheckoutPayHandler;
use Application\Helper\MvcUrlHelper;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Psr\Container\ContainerInterface;

class CheckoutPayHandlerFactory
{
    public function __invoke(ContainerInterface $container): CheckoutPayHandler
    {
        return new CheckoutPayHandler(
            $container->get(FormElementManager::class),
            $container->get(LpaApplicationService::class),
            $container->get('Communication'),
            $container->get('GovPayClient'),
            $container->get(MvcUrlHelper::class),
        );
    }
}
