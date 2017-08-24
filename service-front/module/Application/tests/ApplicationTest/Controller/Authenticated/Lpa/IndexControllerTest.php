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
        $this->controller = new IndexController();
        parent::controllerSetUp($this->controller);

        $this->lpa = new Lpa();
        $this->lpa->id = 123;
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

        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('setMetaData')->with($this->lpa->id, ['analyticsReturnCount' => 1])->once();
        $this->redirect->shouldReceive('toRoute')->with('lpa/form-type', ['lpa-id' => $this->lpa->id], [])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}