<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\General\HomeController;
use Application\ControllerFactory\ControllerAbstractFactory;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ControllerAbstractFactoryTest extends TestCase
{
    /**
     * @var ControllerAbstractFactory
     */
    private $factory;
    /**
     * @var ServiceLocatorInterface
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
        $controller = $this->factory->createServiceWithName($this->serviceLocator, null, 'General\HomeController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Requested controller class is not Dispatchable
     */
    public function testCreateServiceWithNameNotDispatchable()
    {
        $controller = $this->factory->createServiceWithName($this->serviceLocator, null, 'NonDispatchableController');

        $this->assertNotNull($controller);
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
