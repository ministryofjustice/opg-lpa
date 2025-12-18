<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\IndexController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use MakeSharedTest\DataModel\FixturesData;
use Laminas\Http\Response;

final class IndexControllerTest extends AbstractControllerTestCase
{
    public function testIndexActionNoSeed(): void
    {
        /** @var IndexController $controller */
        $controller = $this->getController(IndexController::class);

        $response = new Response();

        $this->lpa->document->type = null;

        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionSeed(): void
    {
        /** @var IndexController $controller */
        $controller = $this->getController(IndexController::class);

        $response = new Response();

        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->seed = $seedLpa->id;

        $this->metadata
            ->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])
            ->once();

        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('clone', $seedLpa->id);

        $this->setRedirectToRoute('lpa/view-docs', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
