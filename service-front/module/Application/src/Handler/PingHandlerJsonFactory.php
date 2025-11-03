<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;
use RuntimeException;

class PingHandlerJsonFactory
{
    public function __invoke(ContainerInterface $container): PingHandlerJson
    {
        try {
            return new PingHandlerJson(
                $container->get('config'),
                $container->get(Status::class),
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException('could not get status service from container', 0, $exception);
        }
    }
}
