<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\AuthenticationServiceFactory;
use Psr\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class AuthenticationServiceFactoryTest extends MockeryTestCase
{
    public function testInvoke(): void
    {
        $authenticationService = Mockery::mock(AdapterInterface::class);

        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['LpaAuthAdapter'])->once()->andReturn($authenticationService);

        $factory = new AuthenticationServiceFactory();

        $result = $factory($container, null, null);

        $this->assertInstanceOf(AuthenticationService::class, $result);
    }
}
