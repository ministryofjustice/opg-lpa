<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\PingHandler;
use Application\Model\Service\System\Status;
use Psr\Container\ContainerInterface;

class PingHandlerFactory
{
    public function __invoke(ContainerInterface $container): PingHandler
    {
        return new PingHandler($container->get(Status::class));
    }
}
