<?php

namespace ApplicationTest\Controller;

use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\User\Details;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;

class AbstractAuthenticatedControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableAbstractAuthenticatedController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(TestableAbstractAuthenticatedController::class);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime());
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

    public function testOnDispatchRedirectToTermsChanged()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->userIdentity = new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime('2014-01-01'));
        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['user/dashboard/terms-changed'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchTermsChangedSeen()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->userIdentity = new UserIdentity($this->user->id, 'token', 60 * 60, new DateTime('2014-01-01'));
        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();

        $this->sessionManager->shouldReceive('start')->once();
        $this->storage->offsetSet('TermsAndConditionsCheck', new ArrayObject(['seen' => true]));

        $this->aboutYouDetails->shouldReceive('load')->andReturn(new User())->once();
        $this->redirect->shouldReceive('toUrl')->withArgs(['/user/about-you/new'])->andReturn($response)->once();

        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->onDispatch($event);
        Container::setDefaultManager(null);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchBadUserData()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->userDetailsSession->user = new InvalidUser(); //Definitely not a user
        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([[
            'clear_storage' => true
        ]])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToNewUser()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $user = new User();
        $this->userDetailsSession->user = $user;
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();
        $this->redirect->shouldReceive('toUrl')->withArgs(['/user/about-you/new'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchLoadUser()
    {
        $event = Mockery::mock(MvcEvent::class);

        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs([
            'Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController',
            $this->userIdentity->toArray()
        ])->once();
        $this->userDetailsSession->user = new User();
        $this->aboutYouDetails->shouldReceive('load')->andReturn(FixturesData::getUser())->once();
        $routeMatch = Mockery::mock(RouteMatch::class);
        $event->shouldReceive('getRouteMatch')->andReturn($routeMatch)->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['action', 'not-found'])->andReturn('index')->once();
        $event->shouldReceive('setResult')/*->withArgs(function ($actionResponse) {
            return $actionResponse->content === 'Placeholder page';
        })*/->once();

        /** @var ViewModel $result */
        $result = $this->controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->content);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A valid Identity has not been set
     */
    public function testGetUserNull()
    {
        $this->controller->getUser();
    }

    public function testGetUserDetailsNull()
    {
        $result = $this->controller->getUserDetails();

        $this->assertNull($result);
    }

    public function testResetSessionCloneData()
    {
        $this->sessionManager->shouldReceive('start')->once();
        $seedId = new ArrayObject(['12345' => '12345']);

        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->testResetSessionCloneData('12345');
        Container::setDefaultManager(null);

        $this->assertNull($result);
    }
}