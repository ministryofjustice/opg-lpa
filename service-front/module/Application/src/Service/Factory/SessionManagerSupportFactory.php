<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Psr\Container\ContainerInterface;

class SessionManagerSupportFactory
{
    public function __invoke(ContainerInterface $container): SessionManagerSupport
    {
        return new SessionManagerSupport(
            $container->get('SessionManager'),
            $container->get(SessionUtility::class),
        );
    }
}
