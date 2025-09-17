<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DeleteController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;

class DeleteControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction()
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(TestableDeleteController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionFailed()
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(TestableDeleteController::class);

        $this->userDetails->shouldReceive('delete')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('error/500.twig', $result->getTemplate());
    }

    public function testConfirmAction()
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(TestableDeleteController::class);

        $response = new Response();

        $this->userDetails->shouldReceive('delete')->andReturn(true)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['deleted'])->andReturn($response)->once();

        $result = $controller->confirmAction();

        $this->assertEquals($response, $result);
    }

    public function testCheckAuthenticated()
    {
        /** @var DeleteController $controller */
        $this->setIdentity(null);
        $controller = $this->getController(TestableDeleteController::class);

        $response = new Response();

        $this->sessionManager->shouldReceive('start')->once();
        $this->request->shouldReceive('getUri')->never();

        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['login', [ 'state' => 'timeout' ]])->andReturn($response)->once();

        Container::setDefaultManager($this->sessionManager);
        $result = $controller->testCheckAuthenticated(true);
        Container::setDefaultManager(null);

        $this->assertEquals($response, $result);
    }
}
