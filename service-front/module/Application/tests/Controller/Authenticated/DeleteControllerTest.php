<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DeleteController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class DeleteControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction(): void
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(DeleteController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testConfirmActionFailed(): void
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(DeleteController::class);

        $this->userDetails->shouldReceive('delete')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->confirmAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('error/500.twig', $result->getTemplate());
    }

    public function testConfirmAction(): void
    {
        /** @var DeleteController $controller */
        $controller = $this->getController(DeleteController::class);

        $response = new Response();

        $this->userDetails->shouldReceive('delete')->andReturn(true)->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['deleted'])->andReturn($response)->once();

        $result = $controller->confirmAction();

        $this->assertEquals($response, $result);
    }
}
