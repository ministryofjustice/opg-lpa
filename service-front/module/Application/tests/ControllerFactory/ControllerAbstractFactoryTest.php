<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\HomeController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManager;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\ServiceManager\AbstractPluginManager;

class ControllerAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var ControllerAbstractFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
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
        $this->container->shouldReceive('get')->withArgs(['FormElementManager'])
            ->andReturn(Mockery::mock(AbstractPluginManager::class))->once();
        $this->container->shouldReceive('get')->withArgs(['SessionManager'])
            ->andReturn(Mockery::mock(SessionManager::class))->once();
        $this->container->shouldReceive('get')->withArgs(['AuthenticationService'])
            ->andReturn(Mockery::mock(AuthenticationService::class))->once();
        $this->container->shouldReceive('get')->withArgs(['Config'])->andReturn([])->once();

        $controller = $this->factory->__invoke($this->container, 'General\HomeController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }
}
