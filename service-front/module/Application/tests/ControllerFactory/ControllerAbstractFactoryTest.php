<?php

declare(strict_types=1);

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\HomeController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Psr\Container\ContainerInterface;
use Laminas\Session\SessionManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\ServiceManager\AbstractPluginManager;

final class ControllerAbstractFactoryTest extends MockeryTestCase
{
    private ControllerAbstractFactory $factory;
    private MockInterface|ContainerInterface $container;

    public function setUp(): void
    {
        $this->factory = new ControllerAbstractFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanCreateServiceWithNameInvalid(): void
    {
        $result = $this->factory->canCreate($this->container, 'Invalid');

        $this->assertFalse($result);
    }

    public function testCanCreateServiceWithName(): void
    {
        $result = $this->factory->canCreate($this->container, 'General\HomeController');

        $this->assertTrue($result);
    }

    public function testCreateServiceWithName(): void
    {
        $this->container->shouldReceive('get')->withArgs(['PersistentSessionDetails'])
            ->andReturn(Mockery::mock(ContainerInterface::class))->once();

        $sessionManager = Mockery::mock(SessionManager::class);
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionManagerSupport = new SessionManagerSupport($sessionManager, $sessionUtility);

        $this->container->shouldReceive('get')->withArgs([SessionManagerSupport::class])
            ->andReturn($sessionManagerSupport)->once();

        $this->container->shouldReceive('get')->withArgs(['FormElementManager'])
            ->andReturn(Mockery::mock(AbstractPluginManager::class))->once();

        $this->container->shouldReceive('get')->withArgs(['AuthenticationService'])
            ->andReturn(Mockery::mock(AuthenticationService::class))->once();

        $this->container->shouldReceive('get')->withArgs(['Config'])->andReturn([])->once();

        $this->container->shouldReceive('get')->withArgs([SessionUtility::class])
            ->andReturn($sessionUtility)->once();

        $controller = $this->factory->__invoke($this->container, 'General\HomeController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }
}
