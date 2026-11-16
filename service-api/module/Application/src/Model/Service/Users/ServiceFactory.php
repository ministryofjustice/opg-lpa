<?php

declare(strict_types=1);

namespace Application\Model\Service\Users;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Applications\Service as ApplicationsService;
use MakeShared\Logging\MetricsLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container): Service
    {
        $config = $container->get('config');

        return new Service(
            $container->get(MetricsLogger::class),
            $container->get(LoggerInterface::class),
            $container->get(UserRepositoryInterface::class),
            $container->get(LogRepositoryInterface::class),
            $container->get(ApplicationRepositoryInterface::class),
            $container->get(ApplicationsService::class),
            $config['authentication_tokens']['use_hash_tokens'],
        );
    }
}
