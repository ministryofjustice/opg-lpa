<?php

namespace ApplicationTest\Controller;

use Application\Model\Service\Authentication\Identity\User;
use DateTime;
use Opg\Lpa\DataModel\Common\Name;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayObject;

class AbstractAuthenticatedControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableAbstractAuthenticatedController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = new TestableAbstractAuthenticatedController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
    }

    public function testOnDispatchNotAuthenticated()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->request->shouldReceive('getUri')->andReturn('http://localhost/home');
        $this->redirect->shouldReceive('toRoute')->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchRedirectToTermsChanged()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime('2014-01-01'));
        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs(['Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController', $this->userIdentity->toArray()])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard/terms-changed'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }

    public function testOnDispatchBadUserData()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->controller->setUser($this->userIdentity);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->logger->shouldReceive('info')->withArgs(['Request to ApplicationTest\Controller\TestableAbstractAuthenticatedController', $this->userIdentity->toArray()])->once();
        $this->userDetailsSession->user = new InvalidUser(); //Definitely not a user
        $this->authenticationService->shouldReceive('clearIdentity')->once();
        $this->sessionManager->shouldReceive('destroy')->withArgs([[
            'clear_storage' => true
        ]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['login', ['state'=>'timeout']])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($result, $response);
    }
}