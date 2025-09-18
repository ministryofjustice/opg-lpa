<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class AuthControllerCookieTest extends AbstractControllerTestCase
{
    public function testIndexActionAlreadySignedIn()
    {
        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $response = new Response();

        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieFails()
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $response = new Response();

        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(1)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['enable-cookie'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieRedirect()
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $response = new Response();

        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', array(), ['query' => ['cookie' => '1']]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExistsFalse()
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $cookie = Mockery::mock(Cookie::class);
        $response = new Response();

        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', array(), ['query' => ['cookie' => '1']]])->andReturn($response)->once();

        $cookie->shouldReceive('offsetExists')->withArgs(['lpa'])->andReturn(false)->once();

        $this->request->shouldReceive('getMethod')->andReturn('GET')->once();
        $this->request->shouldReceive('getCookie')->andReturn($cookie)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExists()
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $cookie = Mockery::mock(Cookie::class);
        $loginForm = new Login();

        $this->url->shouldReceive('fromRoute')->withArgs(['login'])->andReturn('login')->once();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Login'])->andReturn($loginForm)->once();

        $cookie->shouldReceive('offsetExists')->withArgs(['lpa'])->andReturn(true)->once();

        $this->request->shouldReceive('getMethod')->andReturn('GET')->once();
        $this->request->shouldReceive('getCookie')->andReturn($cookie)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionCheckCookiePost()
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $loginForm = new Login();

        $this->url->shouldReceive('fromRoute')->withArgs(['login'])->andReturn('login')->once();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\User\Login'])->andReturn($loginForm)->once();

        $this->request->shouldReceive('getMethod')->andReturn('POST')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }
}
