<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\Service\Session\ContainerNamespace;
use DateTime;
use Mockery;
use MakeShared\DataModel\User\User;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Session\Container;
use Laminas\Stdlib\ArrayObject;
use Laminas\View\Model\ViewModel;

class AbstractAuthenticatedControllerTestCase extends AbstractControllerTestCase
{
    public function testOnDispatchNotAuthenticated(): void
    {
        $this->setIdentity(null);
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $response = new Response();
        $event = new MvcEvent();

        $this->request->shouldReceive('getUri')->andReturn('http://localhost/home');
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state' => 'timeout']])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToTermsChanged(): void
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

    public function testOnDispatchBadUserData(): void
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
            ->withArgs(['login', ['state' => 'timeout']])->andReturn($response)->once();

        $result = $controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToNewUser(): void
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

    public function testOnDispatchLoadUser(): void
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

    public function testOnDispatchDatabaseDown(): void
    {
        // Simulate the database being unavailable, which results in a marker in the session;
        // see Module.php, where this marker is added before the session is handed over to the controller
        $authFailureReason = new Container(ContainerNamespace::AUTH_FAILURE_REASON);
        $authFailureReason->reason = 'Internal system error';
        $authFailureReason->code = 500;

        // Simulate bootstrapIdentity() being unable to find the user; see Module.php
        $this->setIdentity(null);

        // mocks and stubs
        $event = Mockery::mock(MvcEvent::class);
        $response = new Response();

        // expectations
        $this->logger->shouldReceive('info');
        $this->request->shouldReceive('getUri');

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state' => 'internalSystemError']])
            ->andReturn($response)
            ->once();

        // test
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $result = $controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testResetSessionCloneData(): void
    {
        $controller = $this->getController(TestableAbstractAuthenticatedController::class);

        $this->sessionManager->shouldReceive('start')->once();
        new ArrayObject(['12345' => '12345']);

        Container::setDefaultManager($this->sessionManager);
        $result = $controller->testResetSessionCloneData('12345');
        Container::setDefaultManager(null);

        $this->assertNull($result);
    }
}
