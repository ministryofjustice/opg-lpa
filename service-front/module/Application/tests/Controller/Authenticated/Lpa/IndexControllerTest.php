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

        $this->lpa->document->type = null;

        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])->once();
        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/form-type', $result->getHeaders()->get('Location')->getUri());
    }

    public function testIndexActionSeed(): void
    {
        /** @var IndexController $controller */
        $controller = $this->getController(IndexController::class);

        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->seed = $seedLpa->id;

        $this->metadata
            ->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])
            ->once();

        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('clone', $seedLpa->id);

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/view-docs', $result->getHeaders()->get('Location')->getUri());
    }
}
