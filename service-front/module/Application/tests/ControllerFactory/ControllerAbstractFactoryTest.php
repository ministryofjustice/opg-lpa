<?php

declare(strict_types=1);

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\RegisterController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Psr\Container\ContainerInterface;
use Laminas\Session\SessionManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Psr\Log\LoggerInterface;

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
        $result = $this->factory->canCreate($this->container, 'General\RegisterController');

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

        $this->container->shouldReceive('get')->withArgs(['UserService'])
            ->andReturn(Mockery::mock(Details::class))->once();

        $this->container->shouldReceive('get')->withArgs(['Logger'])
            ->andReturn(Mockery::mock(LoggerInterface::class))->once();

        $controller = $this->factory->__invoke($this->container, 'General\RegisterController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(RegisterController::class, $controller);
    }
}
