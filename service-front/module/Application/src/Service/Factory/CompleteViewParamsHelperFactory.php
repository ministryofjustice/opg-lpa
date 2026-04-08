<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\CompleteViewParamsHelper;
use Psr\Container\ContainerInterface;

class CompleteViewParamsHelperFactory
{
    public function __invoke(ContainerInterface $container): CompleteViewParamsHelper
    {
        return new CompleteViewParamsHelper(
            $container->get(MvcUrlHelper::class),
            $container->get(ContinuationSheets::class),
        );
    }
}
