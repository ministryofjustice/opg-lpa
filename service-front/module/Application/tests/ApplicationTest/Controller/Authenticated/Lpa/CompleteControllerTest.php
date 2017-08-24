<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CompleteController;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class CompleteControllerTest extends AbstractControllerTest
{
    /**
     * @var CompleteController
     */
    private $controller;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new CompleteController();
        parent::controllerSetUp($this->controller);

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

    public function testIndexActionGetNotLocked()
    {
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('lockLpa')->with($this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/download', ['lpa-id' => $this->lpa->id, 'pdf-type' => 'lp1'])->andReturn('lpa/download/' . $this->lpa->id . '/pdf/lp1')->once();
        $this->url->shouldReceive('fromRoute')->with('user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id])->andReturn('user/dashboard/create-lpa?seed=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/date-check/complete', ['lpa-id' => $this->lpa->id])->andReturn('lpa/date-check/complete?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/complete/complete.twig', $result->getTemplate());
        $this->assertEquals('lpa/download/' . $this->lpa->id . '/pdf/lp1', $result->getVariable('lp1Url'));
        $this->assertEquals('user/dashboard/create-lpa?seed=' . $this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/date-check/complete?lpa-id=' . $this->lpa->id, $result->getVariable('dateCheckUrl'));
        $this->assertEquals($this->lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->payment->amount, $result->getVariable('paymentAmount'));
        $this->assertEquals($this->lpa->payment->reference, $result->getVariable('paymentReferenceNo'));
        $this->assertEquals(false, $result->getVariable('hasRemission'));
        $this->assertEquals(true, $result->getVariable('isPaymentSkipped'));
        $this->assertEquals([
            'dimension2' => '2017-03-07',
            'dimension3' => 4
        ], $result->getVariable('analyticsDimensions'));
    }
}