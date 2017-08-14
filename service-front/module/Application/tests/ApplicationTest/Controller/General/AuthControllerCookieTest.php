<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\Cookie;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class AuthControllerCookieTest extends AbstractControllerTest
{
    /**
     * @var AuthController
     */
    private $controller;
    /**
     * @var User
     */
    private $identity;

    public function setUp()
    {
        parent::setUp();

        $this->controller = new AuthController();
        $this->controller->setServiceLocator($this->serviceLocator);
        $this->controller->setPluginManager($this->pluginManager);
        $this->controller->setEventManager($this->eventManager);

        $this->identity = Mockery::mock(User::class);
    }

    public function testIndexActionAlreadySignedIn()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->identity)->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieFails()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(1)->once();
        $this->redirect->shouldReceive('toRoute')->with('enable-cookie')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieRedirect()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')->with('login', array(), ['query' => ['cookie' => '1']])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExistsFalse()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);
        $cookie = Mockery::mock(Cookie::class);
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')->with('login', array(), ['query' => ['cookie' => '1']])->andReturn($response)->once();
        $this->responseCollection->shouldReceive('stopped')->andReturn(false)->once();
        $this->controller->dispatch($request);

        $cookie->shouldReceive('offsetExists')->with('lpa')->andReturn(false)->once();

        $request->shouldReceive('getMethod')->andReturn('GET')->once();
        $request->shouldReceive('getCookie')->andReturn($cookie)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExists()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);
        $cookie = Mockery::mock(Cookie::class);
        $loginForm = new Login();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->with('login')->andReturn('login')->once();
        $this->responseCollection->shouldReceive('stopped')->andReturn(false)->once();
        $this->controller->dispatch($request);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\Login')->andReturn($loginForm)->once();

        $cookie->shouldReceive('offsetExists')->with('lpa')->andReturn(true)->once();

        $request->shouldReceive('getMethod')->andReturn('GET')->once();
        $request->shouldReceive('getCookie')->andReturn($cookie)->once();
        $request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionCheckCookiePost()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);
        $loginForm = new Login();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->with('login')->andReturn('login')->once();
        $this->responseCollection->shouldReceive('stopped')->andReturn(false)->once();
        $this->controller->dispatch($request);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\Login')->andReturn($loginForm)->once();

        $request->shouldReceive('getMethod')->andReturn('POST')->once();
        $request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }
}