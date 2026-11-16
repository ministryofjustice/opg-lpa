<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use MakeShared\Logging\MetricsLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container): Service
    {
        return new Service(
            $container->get(MetricsLogger::class),
            $container->get(LoggerInterface::class),
            $container->get(UserRepositoryInterface::class),
            $container->get(LogRepositoryInterface::class),
            $container->get(SharedSpaceRepositoryInterface::class),
            $container->get(AuthenticationService::class),
            $container->get(AuthorisationClientManager::class),
            $container->get(FacileAuthorizationServiceAdapter::class),
            $container->get(LogoutTokenVerifier::class),
            $container->get('OneLoginPsr16Cache'),
            $container->get(KeyPairManager::class),
        );
    }
}
