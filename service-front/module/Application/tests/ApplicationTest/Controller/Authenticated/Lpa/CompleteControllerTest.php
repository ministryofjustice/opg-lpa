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

    public function setUp()
    {
        $this->controller = new CompleteController();
        parent::controllerSetUp($this->controller);
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
        $lpa = FixturesData::getPfLpa();
        $this->controller->setLpa($lpa);
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$lpa->id])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']])
            ->andReturn("lpa/{$lpa->id}/download/pdf/lp1")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $lpa->id]])
            ->andReturn('user/dashboard/create-lpa?seed=' . $lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/date-check/complete', ['lpa-id' => $lpa->id]])
            ->andReturn("lpa/{$lpa->id}/date-check/complete")->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/complete/complete.twig', $result->getTemplate());
        $this->assertEquals("lpa/{$lpa->id}/download/pdf/lp1", $result->getVariable('lp1Url'));
        $this->assertEquals('user/dashboard/create-lpa?seed=' . $lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals("lpa/{$lpa->id}/date-check/complete", $result->getVariable('dateCheckUrl'));
        $this->assertEquals($lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($lpa->payment->amount, $result->getVariable('paymentAmount'));
        $this->assertEquals($lpa->payment->reference, $result->getVariable('paymentReferenceNo'));
        $this->assertEquals(false, $result->getVariable('hasRemission'));
        $this->assertEquals(true, $result->getVariable('isPaymentSkipped'));
        $this->assertEquals([
            'dimension2' => '2017-03-07',
            'dimension3' => 4
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testViewDocsActionPeopleToNotifyFeeReduction()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->payment->reducedFeeUniversalCredit = true;
        $this->controller->setLpa($lpa);
        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$lpa->id])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']])
            ->andReturn("lpa/{$lpa->id}/download/pdf/lp1")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $lpa->id]])
            ->andReturn('user/dashboard/create-lpa?seed=' . $lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/date-check/complete', ['lpa-id' => $lpa->id]])
            ->andReturn("lpa/{$lpa->id}/date-check/complete")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp3']])
            ->andReturn("lpa/{$lpa->id}/download/pdf/lp3")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lpa120']])
            ->andReturn("lpa/{$lpa->id}/download/pdf/lpa120")->once();

        /** @var ViewModel $result */
        $result = $this->controller->viewDocsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals("lpa/{$lpa->id}/download/pdf/lp3", $result->getVariable('lp3Url'));
        $this->assertEquals($lpa->document->peopleToNotify, $result->getVariable('peopleToNotify'));
        $this->assertEquals("lpa/{$lpa->id}/download/pdf/lpa120", $result->getVariable('lpa120Url'));
    }
}
