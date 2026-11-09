<?php

declare(strict_types=1);

namespace Application\Model\Service;

use Application\Model\DataAccess\Postgres\LogData;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DeletionLogCleanupServiceFactory
{
    public function __invoke(ContainerInterface $container): DeletionLogCleanupService
    {
        return new DeletionLogCleanupService(
            $container->get(LogData::class),
            $container->get('NotifyClient'),
            $container->get('config'),
            $container->get(LoggerInterface::class),
        );
    }
}
