<?php

namespace Application\Controller;

use Application\ControllerFactory\UserAwareInitializer;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserAwareInitializerTest extends MockeryTestCase
{
    /**
     * @var UserAwareInitializer
     */
    private $initializer;
    /**
     * @var ContainerInterface
     */
    private $container;
    private $authenticationService;

    public function setUp()
    {
        $this->initializer = new UserAwareInitializer();
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->container->shouldReceive('get')
            ->withArgs(['AuthenticationService'])->andReturn($this->authenticationService);
    }

    public function testInitializeNotAbstractAuthenticatedController()
    {
        $instance = Mockery::mock();
        $instance->shouldReceive('setUser')->never();
        $result = $this->initializer->__invoke($this->container, $instance);
        $this->assertNull($result);
    }

    public function testInitializeNoIdentity()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(false)->once();
        $instance = Mockery::mock(AbstractAuthenticatedController::class);
        $instance->shouldReceive('setUser')->never();
        $result = $this->initializer->__invoke($this->container, $instance);
        $this->assertNull($result);
    }

    public function testInitialize()
    {
        $identity = Mockery::mock(User::class);
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity)->once();
        $instance = Mockery::mock(AbstractAuthenticatedController::class);
        $instance->shouldReceive('setUser')->once();
        $result = $this->initializer->__invoke($this->container, $instance);
        $this->assertNull($result);
    }
}
