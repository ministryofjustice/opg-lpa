<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\HomeController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;
use Zend\Cache\Storage\StorageInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ControllerAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var ControllerAbstractFactory
     */
    private $factory;
    /**
     * @var MockInterface|ServiceLocatorInterface
     */
    private $serviceLocator;

    public function setUp()
    {
        $this->factory = new ControllerAbstractFactory();
        $this->serviceLocator = Mockery::mock(ServiceLocatorInterface::class);
    }

    public function testCanCreateServiceWithNameInvalid()
    {
        $result = $this->factory->canCreateServiceWithName($this->serviceLocator, null, 'Invalid');

        $this->assertFalse($result);
    }

    public function testCanCreateServiceWithName()
    {
        $result = $this->factory->canCreateServiceWithName($this->serviceLocator, null, 'General\HomeController');

        $this->assertTrue($result);
    }

    public function testCreateServiceWithName()
    {
        $this->serviceLocator->shouldReceive('getServiceLocator')->andReturn($this->serviceLocator)->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['FormElementManager'])
            ->andReturn(Mockery::mock(AbstractPluginManager::class))->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['SessionManager'])
            ->andReturn(Mockery::mock(SessionManager::class))->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['AuthenticationService'])
            ->andReturn(Mockery::mock(AuthenticationService::class))->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['Config'])->andReturn([])->once();
        $this->serviceLocator->shouldReceive('get')->withArgs(['Cache'])
            ->andReturn(Mockery::mock(StorageInterface::class))->once();

        $controller = $this->factory->createServiceWithName($this->serviceLocator, null, 'General\HomeController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }
}
