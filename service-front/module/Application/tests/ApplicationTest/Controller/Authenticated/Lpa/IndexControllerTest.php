<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\IndexController;
use ApplicationTest\Controller\AbstractControllerTest;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class IndexControllerTest extends AbstractControllerTest
{
    /**
     * @var IndexController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(IndexController::class);

        $this->lpa = FixturesData::getPfLpa();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionNoSeed()
    {
        $response = new Response();

        $lpa = new Lpa();
        $lpa->id = 123;
        $this->controller->setLpa($lpa);
        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$lpa, 1])->once();
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/form-type', ['lpa-id' => $lpa->id], []])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionSeed()
    {
        $response = new Response();

        $seedLpa = FixturesData::getHwLpa();
        $this->lpa->seed = $seedLpa->id;
        $this->controller->setLpa($this->lpa);
        $this->metadata->shouldReceive('setAnalyticsReturnCount')
            ->withArgs([$this->lpa, 5])->once();

        $this->setRedirectToRoute('lpa/replacement-attorney', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
