<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;

class PingHandlerJsonFactory
{
    public function __invoke(ContainerInterface $container): PingHandlerJson
    {
        return new PingHandlerJson(
            $container->get('config'),
            $container->get(Status::class),
        );
    }
}
