<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Zend\Http\Response;
use Zend\Mvc\Router\RouteStackInterface;

class AuthControllerTest extends AbstractControllerTest
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

        $this->router = Mockery::mock(RouteStackInterface::class);

        $this->controller = new AuthController();
        $this->controller->setServiceLocator($this->serviceLocator);
        $this->controller->setPluginManager($this->pluginManager);

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
}