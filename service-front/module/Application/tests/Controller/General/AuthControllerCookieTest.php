<?php

declare(strict_types=1);

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
    public function testIndexActionAlreadySignedIn(): void
    {
        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/user/dashboard', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionCheckCookieFails(): void
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(1)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/enable-cookie', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionCheckCookieRedirect(): void
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(null)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('login', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionCheckCookieExistsFalse(): void
    {
        $this->setIdentity(null);

        /** @var AuthController $controller */
        $controller = $this->getController(AuthController::class);

        $cookie = Mockery::mock(Cookie::class);

        $this->params->shouldReceive('fromQuery')->withArgs(['cookie'])->andReturn(null)->once();

        $cookie->shouldReceive('offsetExists')->withArgs(['lpa'])->andReturn(false)->once();

        $this->request->shouldReceive('getMethod')->andReturn('GET')->once();
        $this->request->shouldReceive('getCookie')->andReturn($cookie)->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('login', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionCheckCookieExists(): void
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

    public function testIndexActionCheckCookiePost(): void
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
