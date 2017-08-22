<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AdminController;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class AdminControllerTest extends AbstractControllerTest
{
    /**
     * @var AdminController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new AdminController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testOnDispatchEmptyEmail()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => ''];
        $this->userDetailsSession->user = $this->user;
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserNotAdmin()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => 'notadmin@test.com'];
        $this->userDetailsSession->user = $this->user;
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserIsAdminPageNotFound()
    {
        $response = new Response();
        $event = new MvcEvent();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->setRouteMatch($routeMatch);
        $this->controller->setEvent($event);

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => 'admin@test.com'];
        $this->userDetailsSession->user = $this->user;
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->controller->setUser($this->userIdentity);
        $this->logger->shouldReceive('info')->with('Request to ' . AdminController::class, $this->userIdentity->toArray())->once();
        $routeMatch->shouldReceive('getParam')->with('action', 'not-found')->andReturn('not-found')->once();
        $routeMatch->shouldReceive('setParam')->with('action', 'not-found')->once();

        /** @var ViewModel $result */
        $result = $this->controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->getVariable('content'));
    }
}