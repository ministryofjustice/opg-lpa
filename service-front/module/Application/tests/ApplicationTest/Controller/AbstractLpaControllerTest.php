<?php

namespace ApplicationTest\Controller;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use DateTime;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class AbstractLpaControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableAbstractLpaController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(TestableAbstractLpaController::class);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime());

        $this->lpa = FixturesData::getPfLpa();
    }

    public function testOnDispatchNotAuthenticated()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->request->shouldReceive('getUri')->andReturn('http://localhost/home');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchNoLpa()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->controller->setUser($this->userIdentity);
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchNoMethod()
    {
        $response = new Response();
        $this->controller->dispatch($this->request, $response);
        $event = Mockery::mock(MvcEvent::class);

        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->layout->shouldReceive('__invoke')->andReturn($this->layout)->once();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->shouldReceive('getRouteMatch')->andReturn($routeMatch)->twice();
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/unknown')->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn(null)->once();
        $flowChecker = Mockery::mock(FormFlowChecker::class);
        $this->controller->injectedFlowChecker = $flowChecker;
        $flowChecker->shouldReceive('getNearestAccessibleRoute')
            ->withArgs(['lpa/unknown', null])->andReturn(false)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchCalculatedRouteNotEqual()
    {
        $response = new Response();
        $event = Mockery::mock(MvcEvent::class);

        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->layout->shouldReceive('__invoke')->andReturn($this->layout)->once();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->shouldReceive('getRouteMatch')->andReturn($routeMatch)->twice();
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/donor')->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn(null)->once();
        $flowChecker = Mockery::mock(FormFlowChecker::class);
        $this->controller->injectedFlowChecker = $flowChecker;
        $flowChecker->shouldReceive('getNearestAccessibleRoute')
            ->withArgs(['lpa/donor', null])->andReturn('lpa/checkout')->once();
        $flowChecker->shouldReceive('getRouteOptions')->withArgs(['lpa/checkout'])->andReturn([])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/checkout', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchDownload()
    {
        $event = Mockery::mock(MvcEvent::class);

        $this->controller->setUser($this->userIdentity);
        $this->controller->setLpa($this->lpa);
        $this->layout->shouldReceive('__invoke')->andReturn($this->layout)->once();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->shouldReceive('getRouteMatch')->andReturn($routeMatch)->times(3);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/download')->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn('lp1')->once();
        $flowChecker = Mockery::mock(FormFlowChecker::class);
        $this->controller->injectedFlowChecker = $flowChecker;
        $flowChecker->shouldReceive('getNearestAccessibleRoute')
            ->withArgs(['lpa/download', 'lp1'])->andReturn('lpa/download')->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractLpaController',
            $this->userIdentity->toArray()
        ])->once();
        $this->userDetailsSession->user = $this->user;
        $routeMatch->shouldReceive('getParam')->withArgs(['action', 'not-found'])->andReturn('index')->once();
        $event->shouldReceive('setResult')/*->withArgs(function ($actionResponse) {
            return $actionResponse instanceof ViewModel;
        })*/->once();

        /** @var ViewModel $result */
        $result = $this->controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->content);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage RouteMatch must be an instance of Zend\Mvc\Router\Http\RouteMatch when using the moveToNextRoute function
     */
    public function testMoveToNextRouteNotRouteMatch()
    {
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->controller->testMoveToNextRoute();
    }
}