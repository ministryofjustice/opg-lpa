<?php

namespace ApplicationTest\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\AuthenticationServiceFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class AuthenticationServiceFactoryTest extends MockeryTestCase
{
    public function testInvoke()
    {
        $authenticationService = Mockery::mock(AdapterInterface::class);

        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')->withArgs(['LpaAuthAdapter'])->once()->andReturn($authenticationService);

        $factory = new AuthenticationServiceFactory();

        $result = $factory($container, null, null);

        $this->assertInstanceOf(AuthenticationService::class, $result);
    }
}
