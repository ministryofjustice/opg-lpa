<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Model\Service\User\Delete;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
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
     * @var MockInterface|Delete
     */
    private $delete;

    public function setUp()
    {
        $this->controller = new TestableDeleteController();
        parent::controllerSetUp($this->controller);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionFailed()
    {
        $this->delete = Mockery::mock(Delete::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['DeleteUser'])->andReturn($this->delete)->once();
        $this->delete->shouldReceive('delete')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('error/500', $result->getTemplate());
    }

    public function testConfirmAction()
    {
        $response = new Response();

        $this->delete = Mockery::mock(Delete::class);
        $this->serviceLocator->shouldReceive('get')->withArgs(['DeleteUser'])->andReturn($this->delete)->once();
        $this->delete->shouldReceive('delete')->andReturn(true)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['deleted'])->andReturn($response)->once();

        $result = $this->controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testCheckAuthenticated()
    {
        $response = new Response();

        $this->storage->shouldReceive('offsetExists')->withArgs(['PreAuthRequest'])->andReturn(true)->never();
        $this->sessionManager->shouldReceive('start')->never();
        $preAuthRequest = new ArrayObject(['url' => 'https://localhost/user/about-you']);
        $this->storage->shouldReceive('offsetGet')->withArgs(['PreAuthRequest'])->andReturn($preAuthRequest)->never();
        $this->storage->shouldReceive('getMetadata')->withArgs(['PreAuthRequest'])->never();
        $this->storage->shouldReceive('getRequestAccessTime')->never();
        $this->request->shouldReceive('getUri')->never();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', [ 'state'=>'timeout' ]])->andReturn($response)->once();

        Container::setDefaultManager($this->sessionManager);
        $result = $this->controller->testCheckAuthenticated(true);
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }
}
