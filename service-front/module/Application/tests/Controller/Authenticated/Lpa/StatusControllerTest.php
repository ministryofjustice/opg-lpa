<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;
use Application\Controller\Authenticated\DashboardController;
use Application\Controller\Authenticated\Lpa\StatusController;
use ApplicationTest\Controller\AbstractControllerTest;
use ApplicationTest\Controller\Authenticated\TestableDashboardController;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Zend\View\Model\ViewModel;
use Zend\Http\Response;

class StatusControllerTest extends AbstractControllerTest
{
    public function testIndexAction()
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionInvalidStatus()
    {/** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $status = "InvalidStatus";
        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>$status]]);

        $response = new Response();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @param $status
     * @dataProvider  statusProvider
     */
    public function testIndexActionWithValidStatuses($status)
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>$status]]);

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);

    }
    public function statusProvider()
    {
        return[
            ['waiting'],
            ['received'],
            ['checking'],
            ['returned'],
            ['completed']
        ];
    }

    public function testIndexActionResultContainsCanGenerateLPA120()
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>'Waiting']]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();
        $canGenerateLPA120 = $result->getVariable('canGenerateLPA120');

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertFalse($canGenerateLPA120);
    }
}