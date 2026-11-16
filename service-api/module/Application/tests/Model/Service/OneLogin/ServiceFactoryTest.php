<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\OneLogin\AuthorisationClientManager;
use Application\Model\Service\OneLogin\AuthorizationServiceInterface;
use Application\Model\Service\OneLogin\FacileAuthorizationServiceAdapter;
use Application\Model\Service\OneLogin\KeyPairManager;
use Application\Model\Service\OneLogin\LogoutTokenVerifier;
use Application\Model\Service\OneLogin\Service;
use Application\Model\Service\OneLogin\ServiceFactory;
use MakeShared\Logging\MetricsLogger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class ServiceFactoryTest extends MockeryTestCase
{
    public function testInvokeCreatesService(): void
    {
        $dependencies = [
            MetricsLogger::class => Mockery::mock(MetricsLogger::class),
            LoggerInterface::class => Mockery::mock(LoggerInterface::class),
            UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
            LogRepositoryInterface::class => Mockery::mock(LogRepositoryInterface::class),
            SharedSpaceRepositoryInterface::class => Mockery::mock(SharedSpaceRepositoryInterface::class),
            AuthenticationService::class => Mockery::mock(AuthenticationService::class),
            AuthorisationClientManager::class => Mockery::mock(AuthorisationClientManager::class),
            FacileAuthorizationServiceAdapter::class => Mockery::mock(AuthorizationServiceInterface::class),
            LogoutTokenVerifier::class => Mockery::mock(LogoutTokenVerifier::class),
            'OneLoginPsr16Cache' => Mockery::mock(CacheInterface::class),
            KeyPairManager::class => Mockery::mock(KeyPairManager::class),
        ];

        $container = Mockery::mock(ContainerInterface::class);
        foreach ($dependencies as $id => $dependency) {
            $container->shouldReceive('get')->with($id)->once()->andReturn($dependency);
        }

        $this->assertInstanceOf(Service::class, (new ServiceFactory())($container));
    }
}
