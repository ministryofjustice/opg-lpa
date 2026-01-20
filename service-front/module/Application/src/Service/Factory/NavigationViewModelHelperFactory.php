<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Lpa\Application;
use Application\Model\Service\Session\SessionUtility;
use Application\Service\NavigationViewModelHelper;
use Psr\Container\ContainerInterface;

class NavigationViewModelHelperFactory
{
    public function __invoke(ContainerInterface $container): NavigationViewModelHelper
    {
        return new NavigationViewModelHelper(
            $container->get(SessionUtility::class),
            $container->get(Application::class),
        );
    }
}
