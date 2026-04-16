<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\CheckoutChequeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

class CheckoutChequeHandlerFactory
{
    public function __invoke(ContainerInterface $container): CheckoutChequeHandler
    {
        return new CheckoutChequeHandler(
            $container->get(LpaApplicationService::class),
            $container->get('Communication'),
            $container->get(MvcUrlHelper::class),
        );
    }
}
