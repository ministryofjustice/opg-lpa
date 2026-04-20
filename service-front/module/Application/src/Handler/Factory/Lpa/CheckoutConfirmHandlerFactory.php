<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\CheckoutConfirmHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class CheckoutConfirmHandlerFactory
{
    public function __invoke(ContainerInterface $container): CheckoutConfirmHandler
    {
        return new CheckoutConfirmHandler(
            $container->get(LpaApplicationService::class),
            $container->get('Communication'),
            $container->get(MvcUrlHelper::class),
        );
    }
}
