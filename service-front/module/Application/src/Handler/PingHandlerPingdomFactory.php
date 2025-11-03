<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;
use RuntimeException;

class PingHandlerPingdomFactory
{
    public function __invoke(ContainerInterface $container): PingHandlerPingdom
    {
        try {
            return new PingHandlerPingdom(
                $container->get(Status::class),
                new DateService(),
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException('could not get a service from container', 0, $exception);
        }
    }
}
