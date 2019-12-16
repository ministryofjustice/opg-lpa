<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\RegisterController;
use Application\Form\User\ConfirmEmail;
use Application\Form\User\Registration;
use Application\Model\Service\User\Details;
use Application\Model\Service\User\Register;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\Referer;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class RegisterControllerTest extends AbstractControllerTest
{
    const GA = 987654321987654321;
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

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(Registration::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Registration'])->andReturn($this->form);
    }

    protected function getController(string $controllerName)
    {
        /** @var RegisterController $controller */
        $controller = parent::getController($controllerName);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->event = Mockery::mock(MvcEvent::class);
        $this->event->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $controller->setEvent($this->event);

        $this->userDetails = Mockery::mock(Details::class);
        $controller->setUserService($this->userDetails);

        return $controller;
    }

    public function testIndexActionRefererGovUk()
    {
        $controller = $this->getController(RegisterController::class);

        $response = new Response();
        $referer = new Referer();
        $referer->setUri('http://www.gov.uk');

        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['home', ['action' => 'index'], ['query' => ['_ga' => self::GA]]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionAlreadyLoggedIn()
    {
        $controller = $this->getController(RegisterController::class);

        $response = new Response();

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();
        $this->logger->shouldReceive('info')
            ->withArgs(['Authenticated user attempted to access registration page', $this->userIdentity->toArray()])
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionGet()
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testIndexActionPostError()
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('registerAccount')->andReturn('Unit test error')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
        $this->assertEquals('Unit test error', $result->getVariable('error'));
    }

    public function testIndexActionPostSuccess()
    {
        $this->setIdentity(null);
        $controller = $this->getController(RegisterController::class);

        $response = new Response();

        $referer = new Referer();
        $referer->setUri('https://localhost/home');
        $this->request->shouldReceive('getQuery')->withArgs(['_ga'])->andReturn(self::GA)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['register'])->andReturn('register')->once();
        $this->form->shouldReceive('setAttribute')->withArgs(['action', 'register'])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->userDetails->shouldReceive('registerAccount')->andReturn(true);

        //  Set up the confirm email form
        $this->url->shouldReceive('fromRoute')->withArgs(['register/resend-email'])->andReturn('register/resend-email')->once();
        $form = Mockery::mock(ConfirmEmail::class);
        $form->shouldReceive('setAttribute')->withArgs(['action', 'register/resend-email'])->once();
        $form->shouldReceive('populateValues')->withArgs([[
            'email' => $this->postData['email'],
            'email_confirm' => $this->postData['email'],
        ]])->once();

        $this->formElementManager->shouldReceive('get')
             ->withArgs(['Application\Form\User\ConfirmEmail'])->andReturn($form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionNoToken()
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('invalid-token', $result->getVariable('error'));
    }

    public function testConfirmActionAccountDoesNotExist()
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->sessionManager->shouldReceive('initialise')->once();
        $this->userDetails->shouldReceive('activateAccount')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('account-missing', $result->getVariable('error'));
    }

    public function testConfirmActionSuccess()
    {
        $controller = $this->getController(RegisterController::class);

        $this->params->shouldReceive('fromRoute')->withArgs(['token'])->andReturn('unitTest')->once();
        $this->authenticationService->shouldReceive('clearIdentity');
        $this->sessionManager->shouldReceive('initialise')->once();
        $this->userDetails->shouldReceive('activateAccount')->andReturn(true)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
}
