<?php

namespace Application\Controller;

use Application\Controller\Authenticated\Lpa\IndexController;
use Application\ControllerFactory\LpaAwareInitializer;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;

class LpaAwareInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LpaAwareInitializer
     */
    private $initializer;
    /**
     * @var ServiceLocatorInterface
     */
    private $serviceLocator;
    private $authenticationService;
    private $application;
    private $mvcEvent;
    private $routeMatch;
    private $lpaApplicationService;

    public function setUp()
    {
        $this->initializer = new LpaAwareInitializer();
        $this->serviceLocator = Mockery::mock(ServiceLocatorInterface::class);
        $this->serviceLocator->shouldReceive('getServiceLocator')->andReturn($this->serviceLocator);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->serviceLocator->shouldReceive('get')->with('AuthenticationService')->andReturn($this->authenticationService);
        $this->application = Mockery::mock(Application::class);
        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->mvcEvent->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $this->application->shouldReceive('getMvcEvent')->andReturn($this->mvcEvent);
        $this->serviceLocator->shouldReceive('get')->with('Application')->andReturn($this->application);
        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $this->serviceLocator->shouldReceive('get')->with('LpaApplicationService')->andReturn($this->lpaApplicationService);
    }

    public function testInitializeNotAbstractLpaController()
    {
        $result = $this->initializer->initialize('Test', $this->serviceLocator);
        $this->assertNull($result);
    }

    public function testInitializeNoUser()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(false);
        $instance = new IndexController();
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid LPA ID passed
     */
    public function testInitializeInvalidLpaId()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true);
        $this->routeMatch->shouldReceive('getParam')->with('lpa-id')->andReturn('invalid');
        $instance = new IndexController();
        $this->initializer->initialize($instance, $this->serviceLocator);
    }

    public function testInitializeLpaNull()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true);
        $this->routeMatch->shouldReceive('getParam')->with('lpa-id')->andReturn(1);
        $this->lpaApplicationService->shouldReceive('getApplication')->with(1)->andReturn(null);
        $instance = new IndexController();
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid LPA ID
     */
    public function testInitializeLpaUserMismatch()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true);
        $this->routeMatch->shouldReceive('getParam')->with('lpa-id')->andReturn(1);
        $lpa = new Lpa();
        $lpa->user = '54321';
        $this->lpaApplicationService->shouldReceive('getApplication')->with(1)->andReturn($lpa);
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->andReturn('12345');
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity);
        $instance = new IndexController();
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    public function testInitializeLpa()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true);
        $this->routeMatch->shouldReceive('getParam')->with('lpa-id')->andReturn(1);
        $lpa = new Lpa();
        $lpa->user = '12345';
        $this->lpaApplicationService->shouldReceive('getApplication')->with(1)->andReturn($lpa);
        $identity = Mockery::mock(IndexController::class);
        $identity->shouldReceive('id')->andReturn('12345');
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity);
        $instance = Mockery::mock(IndexController::class);
        $instance->shouldReceive('setLpa')->with($lpa);
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
        $instance->mockery_verify();
    }

    public function tearDown()
    {
        Mockery::close();
    }
}