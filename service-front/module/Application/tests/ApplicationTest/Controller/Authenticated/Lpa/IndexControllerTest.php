<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\IndexController;
use ApplicationTest\Controller\AbstractControllerTest;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Http\Response;

class IndexControllerTest extends AbstractControllerTest
{
    /**
     * @var IndexController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(IndexController::class);
    }

    public function testIndexActionNoSeed()
    {
        $response = new Response();

        $this->lpa->document->type = null;

        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $this->lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionSeed()
    {
        $response = new Response();

        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->seed = $seedLpa->id;

        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])->once();

        $this->setRedirectToRoute('lpa/view-docs', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
