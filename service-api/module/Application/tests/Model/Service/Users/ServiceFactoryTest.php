<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Users;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Users\Service;
use Application\Model\Service\Users\ServiceFactory;
use MakeShared\Logging\MetricsLogger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactoryTest extends MockeryTestCase
{
    public function testInvokeCreatesService(): void
    {
        $dependencies = [
            'config' => ['authentication_tokens' => ['use_hash_tokens' => false]],
            MetricsLogger::class => Mockery::mock(MetricsLogger::class),
            LoggerInterface::class => Mockery::mock(LoggerInterface::class),
            UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
            LogRepositoryInterface::class => Mockery::mock(LogRepositoryInterface::class),
            ApplicationRepositoryInterface::class => Mockery::mock(ApplicationRepositoryInterface::class),
            ApplicationsService::class => Mockery::mock(ApplicationsService::class),
        ];

        $container = Mockery::mock(ContainerInterface::class);
        foreach ($dependencies as $id => $dependency) {
            $container->shouldReceive('get')->with($id)->once()->andReturn($dependency);
        }

        $this->assertInstanceOf(Service::class, (new ServiceFactory())($container));
    }
}
