<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\DateCheckViewModelHelper;
use Psr\Container\ContainerInterface;

final class DateCheckViewModelHelperFactory
{
    public function __invoke(ContainerInterface $container): DateCheckViewModelHelper
    {
        return new DateCheckViewModelHelper(
            $container->get(ContinuationSheets::class),
        );
    }
}
