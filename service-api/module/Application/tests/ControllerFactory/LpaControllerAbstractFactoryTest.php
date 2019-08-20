<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\Version2\Lpa\ApplicationController;
use Application\ControllerFactory\LpaControllerAbstractFactory;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use ZfcRbac\Service\AuthorizationService;

class LpaControllerAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var LpaControllerAbstractFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new LpaControllerAbstractFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanCreateServiceWithNameInvalid()
    {
        $result = $this->factory->canCreate($this->container, 'Invalid');

        $this->assertFalse($result);
    }

    public function testCanCreateServiceWithName()
    {
        $result = $this->factory->canCreate($this->container, ApplicationController::class);

        $this->assertTrue($result);
    }

    public function testCreateServiceWithName()
    {
        $this->container->shouldReceive('get')->with(AuthorizationService::class)
            ->andReturn(Mockery::mock(AuthorizationService::class))->once();
        $this->container->shouldReceive('get')->with(ApplicationsService::class)
            ->andReturn(Mockery::mock(ApplicationsService::class))->once();

        $controller = $this->factory->__invoke($this->container, ApplicationController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(ApplicationController::class, $controller);
    }

    public function testCreateServiceWithNameThrowsException()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Abstract factory Application\ControllerFactory\LpaControllerAbstractFactory can not create the requested service NotAController');

        $this->factory->__invoke($this->container, 'NotAController');
    }
}
