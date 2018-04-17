<?php

namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractControllerTest;
use Zend\Http\Response;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;
use Zend\View\Model\ViewModel;

class DeleteControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableDeleteController
     */
    private $controller;

    /**
     * @param bool $setUpIdentity
     */
    public function setUpController($setUpIdentity = true)
    {
        $this->controller = parent::controllerSetUp(TestableDeleteController::class, $setUpIdentity);
    }

    public function testIndexAction()
    {
        $this->setUpController();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionFailed()
    {
        $this->setUpController();

        $this->userDetails->shouldReceive('delete')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('error/500.twig', $result->getTemplate());
    }

    public function testConfirmAction()
    {
        $this->setUpController();

        $response = new Response();

        $this->userDetails->shouldReceive('delete')->andReturn(true)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['deleted'])->andReturn($response)->once();

        $result = $this->controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testCheckAuthenticated()
    {
        $this->setUpController(false);

        $response = new Response();

        $this->sessionManager->shouldReceive('start')->never();
        $preAuthRequest = new ArrayObject(['url' => 'https://localhost/user/about-you']);
        $this->request->shouldReceive('getUri')->never();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', [ 'state'=>'timeout' ]])->andReturn($response)->once();

        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->testCheckAuthenticated(true);
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }
}
