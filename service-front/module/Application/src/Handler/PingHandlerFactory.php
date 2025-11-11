<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;

class PingHandlerFactory
{
    public function __invoke(ContainerInterface $container): PingHandler
    {
        return new PingHandler($container->get(Status::class));
    }
}
