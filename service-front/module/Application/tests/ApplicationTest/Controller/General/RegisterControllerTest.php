<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\RegisterController;
use Application\Form\User\Registration;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\User\Register;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Header\Referer;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class RegisterControllerTest extends AbstractControllerTest
{
    /**
     * @var RegisterController
     */
    private $controller;
    /**
     * @var MockInterface|Registration
     */
    private $form;
    /**
     * @var MockInterface|MvcEvent
     */
    private $event;
    /**
     * @var MockInterface|RouteMatch
     */
    private $routeMatch;
    private $postData = [
        'email' => 'unit@test.com',
        'password' => 'password'
    ];
    /**
     * @var MockInterface|Register
     */
    private $register;

    public function setUp()
    {
        $this->controller = new RegisterController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(Registration::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Registration'])->andReturn($this->form);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->event = Mockery::mock(MvcEvent::class);
        $this->controller->setEvent($this->event);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);

        $this->register = Mockery::mock(Register::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['Register'])->andReturn($this->register);
    }

    public function testIndexActionRefererGovUk()
    {
        $response = new Response();
        $referer = new Referer();
        $referer->setUri('http://www.gov.uk');

        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionAlreadyLoggedIn()
    {
        $response = new Response();

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->twice();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();
        $this->logger->shouldReceive('info')
            ->withArgs(['Authenticated user attempted to access registration page', $this->userIdentity->toArray()])
            ->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionGet()
    {
        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostInvalid()
    {
        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostError()
    {
        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->register->shouldReceive('registerAccount')->andReturn('Unit test error')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
        $this->assertEquals('Unit test error', $result->getVariable('error'));
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->register->shouldReceive('registerAccount')->andReturn(true);

        $this->redirect->shouldReceive('toRoute')->withArgs(['register/email-sent', [], [
            'query' => [
                'email' => 'unit@test.com'
            ]
        ]])->andReturn($response)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmActionNoToken()
    {
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('invalid-token', $result->getVariable('error'));
    }

    public function testConfirmActionAccountDoesNotExist()
    {
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->storage->shouldReceive('clear')->once();
        $this->sessionManager->shouldReceive('initialise')->once();
        $this->register->shouldReceive('activateAccount')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('account-missing', $result->getVariable('error'));
    }

    public function testConfirmActionSuccess()
    {
        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->storage->shouldReceive('clear')->once();
        $this->sessionManager->shouldReceive('initialise')->once();
        $this->register->shouldReceive('activateAccount')->andReturn(true)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
}
