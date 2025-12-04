<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Stats\Stats as StatsService;
use Psr\Container\ContainerInterface;

class StatsHandlerFactory
{
    public function __invoke(ContainerInterface $container): StatsHandler
    {
        return new StatsHandler($container->get(StatsService::class));
    }
}
