<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CompleteController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use Laminas\View\Model\ViewModel;

final class CompleteControllerTest extends AbstractControllerTestCase
{
    public function testIndexActionGetNotLocked(): void
    {
        /** @var CompleteController $controller */
        $controller = $this->getController(CompleteController::class);

        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $this->lpa->id, 'pdf-type' => 'lp1']])
            ->andReturn("lpa/{$this->lpa->id}/download/pdf/lp1")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?seed=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/date-check/complete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/date-check/complete")->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/complete/complete.twig', $result->getTemplate());
        $this->assertEquals("lpa/{$this->lpa->id}/download/pdf/lp1", $result->getVariable('lp1Url'));
        $this->assertEquals('user/dashboard/create-lpa?seed=' . $this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals("lpa/{$this->lpa->id}/date-check/complete", $result->getVariable('dateCheckUrl'));
        $this->assertEquals($this->lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->payment->amount, $result->getVariable('paymentAmount'));
        $this->assertEquals($this->lpa->payment->reference, $result->getVariable('paymentReferenceNo'));
        $this->assertEquals(false, $result->getVariable('hasRemission'));
        $this->assertEquals(true, $result->getVariable('isPaymentSkipped'));
    }

    public function testViewDocsActionPeopleToNotifyFeeReduction(): void
    {
        /** @var CompleteController $controller */
        $controller = $this->getController(CompleteController::class);

        $this->lpa->payment->reducedFeeUniversalCredit = true;

        $this->lpa->document->peopleToNotify = [
            new NotifiedPerson(),
        ];

        $this->lpaApplicationService->shouldReceive('lockLpa')->withArgs([$this->lpa])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $this->lpa->id, 'pdf-type' => 'lp1']])
            ->andReturn("lpa/{$this->lpa->id}/download/pdf/lp1")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?seed=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/date-check/complete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/date-check/complete")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $this->lpa->id, 'pdf-type' => 'lp3']])
            ->andReturn("lpa/{$this->lpa->id}/download/pdf/lp3")->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/download', ['lpa-id' => $this->lpa->id, 'pdf-type' => 'lpa120']])
            ->andReturn("lpa/{$this->lpa->id}/download/pdf/lpa120")->once();

        /** @var ViewModel $result */
        $result = $controller->viewDocsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals("lpa/{$this->lpa->id}/download/pdf/lp3", $result->getVariable('lp3Url'));
        $this->assertEquals($this->lpa->document->peopleToNotify, $result->getVariable('peopleToNotify'));
        $this->assertEquals("lpa/{$this->lpa->id}/download/pdf/lpa120", $result->getVariable('lpa120Url'));
    }
}
