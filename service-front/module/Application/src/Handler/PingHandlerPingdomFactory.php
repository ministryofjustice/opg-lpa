<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;

class PingHandlerPingdomFactory
{
    public function __invoke(ContainerInterface $container): PingHandlerPingdom
    {
        return new PingHandlerPingdom(
            $container->get(Status::class),
            new DateService(),
        );
    }
}
