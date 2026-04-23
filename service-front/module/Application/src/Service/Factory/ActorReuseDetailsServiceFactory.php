<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionUtility;
use Psr\Container\ContainerInterface;

class ActorReuseDetailsServiceFactory
{
    public function __invoke(ContainerInterface $container): ActorReuseDetailsService
    {
        return new ActorReuseDetailsService(
            $container->get(LpaApplicationService::class),
            $container->get(SessionUtility::class),
        );
    }
}
