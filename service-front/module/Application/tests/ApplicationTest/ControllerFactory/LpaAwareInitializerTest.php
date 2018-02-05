<?php

namespace Application\Controller;

use Application\Controller\Authenticated\Lpa\IndexController;
use Application\ControllerFactory\LpaAwareInitializer;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;

class LpaAwareInitializerTest extends MockeryTestCase
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
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['AuthenticationService'])->andReturn($this->authenticationService);
        $this->application = Mockery::mock(Application::class);
        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->mvcEvent->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $this->application->shouldReceive('getMvcEvent')->andReturn($this->mvcEvent);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Application'])->andReturn($this->application);
        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $this->serviceLocator->shouldReceive('get')
            ->withArgs(['LpaApplicationService'])->andReturn($this->lpaApplicationService);
    }

    public function testInitializeNotAbstractLpaController()
    {
        $result = $this->initializer->initialize('Test', $this->serviceLocator);
        $this->assertNull($result);
    }

    public function testInitializeNoUser()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(false)->once();
        $instance = Mockery::mock(IndexController::class);
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid LPA ID passed
     */
    public function testInitializeInvalidLpaId()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true)->once();
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn('invalid')->once();
        $instance = Mockery::mock(IndexController::class);
        $this->initializer->initialize($instance, $this->serviceLocator);
    }

    public function testInitializeLpaNull()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true)->once();
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([1])->andReturn(null)->once();
        $instance = Mockery::mock(IndexController::class);
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid LPA ID
     */
    public function testInitializeLpaUserMismatch()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true)->once();
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $lpa = new Lpa();
        $lpa->user = '54321';
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([1])->andReturn($lpa)->once();
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->andReturn('12345')->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity)->once();
        $instance = Mockery::mock(IndexController::class);
        $instance->shouldReceive('setLpa')->never();
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }

    public function testInitializeLpa()
    {
        $this->authenticationService->shouldReceive('hasIdentity')->andReturn(true)->once();
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpa-id'])->andReturn(1)->once();
        $lpa = new Lpa();
        $lpa->user = '12345';
        $this->lpaApplicationService->shouldReceive('getApplication')->withArgs([1])->andReturn($lpa)->once();
        $identity = Mockery::mock(User::class);
        $identity->shouldReceive('id')->andReturn('12345')->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity)->once();
        $instance = Mockery::mock(IndexController::class);
        $instance->shouldReceive('setLpa')->withArgs([$lpa])->once();
        $result = $this->initializer->initialize($instance, $this->serviceLocator);
        $this->assertNull($result);
    }
}
