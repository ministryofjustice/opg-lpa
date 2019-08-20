<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\Version2\Auth\EmailController;
use Application\ControllerFactory\AuthControllerAbstractFactory;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Email\Service as EmailService;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class AuthControllerAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var AuthControllerAbstractFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new AuthControllerAbstractFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanCreateServiceWithNameInvalid()
    {
        $result = $this->factory->canCreate($this->container, 'Invalid');

        $this->assertFalse($result);
    }

    public function testCanCreateServiceWithName()
    {
        $result = $this->factory->canCreate($this->container, EmailController::class);

        $this->assertTrue($result);
    }

    public function testCreateServiceWithName()
    {
        $this->container->shouldReceive('get')
            ->with(AuthenticationService::class)
            ->andReturn(Mockery::mock(AuthenticationService::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with(EmailService::class)
            ->andReturn(Mockery::mock(EmailService::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, EmailController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(EmailController::class, $controller);
    }

    public function testCreateServiceWithNameThrowsException()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Abstract factory Application\ControllerFactory\AuthControllerAbstractFactory can not create the requested service NotAController');

        $this->factory->__invoke($this->container, 'NotAController');
    }
}
