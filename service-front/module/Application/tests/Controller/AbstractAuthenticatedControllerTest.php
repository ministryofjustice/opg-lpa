<?php

namespace ApplicationTest\Controller;

use DateTime;
use Mockery;
use Opg\Lpa\DataModel\User\User;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;

class AbstractAuthenticatedControllerTest extends AbstractControllerTest
{
    public function testOnDispatchNotAuthenticated()
    {
        $this->setIdentity(null);
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $response = new Response();
        $event = new MvcEvent();

        $this->request->shouldReceive('getUri')->andReturn('http://localhost/home');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToTermsChanged()
    {
        $now = new DateTime();
        $this->config['terms']['lastUpdated'] = $now->format('Y-m-d H:i T');

        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $response = new Response();
        $event = new MvcEvent();

        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['user/dashboard/terms-changed'])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchBadUserData()
    {
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('get')->andReturn('name');

        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $response = new Response();
        $event = new MvcEvent();

        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([[
            'clear_storage' => true
        ]])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToNewUser()
    {
        $this->user = new User();

        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $response = new Response();
        $event = new MvcEvent();

        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->redirect->shouldReceive('toUrl')->withArgs(['/user/about-you/new'])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchLoadUser()
    {
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $event = Mockery::mock(MvcEvent::class);

        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->shouldReceive('getRouteMatch')->andReturn($routeMatch)->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['action', 'not-found'])->andReturn('index')->once();
        $event->shouldReceive('setResult')/*->withArgs(function ($actionResponse) {
            return $actionResponse->content === 'Placeholder page';
        })*/->once();

        /** @var ViewModel $result */
        $result = $controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->content);
    }

    public function testResetSessionCloneData()
    {
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $this->sessionManager->shouldReceive('start')->once();
        $seedId = new ArrayObject(['12345' => '12345']);

        Container::setDefaultManager($this->sessionManager);
        $result = $controller->testResetSessionCloneData('12345');
        Container::setDefaultManager(null);

        $this->assertNull($result);
    }
}