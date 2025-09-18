<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\HomeController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application;
use Application\Model\Service\Session\SessionManager;
use Interop\Container\ContainerInterface;
use Laminas\Router\RouteMatch;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\ServiceManager\AbstractPluginManager;

final class ControllerAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var ControllerAbstractFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp() : void
    {
        $this->factory = new ControllerAbstractFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanCreateServiceWithNameInvalid()
    {
        $result = $this->factory->canCreate($this->container, 'Invalid');

        $this->assertFalse($result);
    }

    public function testCanCreateServiceWithName()
    {
        $result = $this->factory->canCreate($this->container, 'General\HomeController');

        $this->assertTrue($result);
    }

    public function testCreateServiceWithName()
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/applicant');

        $mvcEvent = Mockery::mock(RouteMatch::class);
        $mvcEvent->shouldReceive('getRouteMatch')->withArgs([])
            ->andReturn($routeMatch)->once();

        $application = Mockery::mock(Application::class);
        $application->shouldReceive('getMvcEvent')->withArgs([])
            ->andReturn($mvcEvent)->once();
        $this->container->shouldReceive('get')->withArgs(['Application'])
            ->andReturn($application)->once();

        $this->container->shouldReceive('get')->withArgs(['PersistentSessionDetails'])
            ->andReturn(Mockery::mock(ContainerInterface::class))->once();

        $this->container->shouldReceive('get')->withArgs(['SessionManager'])
            ->andReturn(Mockery::mock(SessionManager::class))->once();

        $this->container->shouldReceive('get')->withArgs(['FormElementManager'])
            ->andReturn(Mockery::mock(AbstractPluginManager::class))->once();

        $this->container->shouldReceive('get')->withArgs(['AuthenticationService'])
            ->andReturn(Mockery::mock(AuthenticationService::class))->once();

        $this->container->shouldReceive('get')->withArgs(['Config'])->andReturn([])->once();

        $controller = $this->factory->__invoke($this->container, 'General\HomeController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }
}
