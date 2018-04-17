<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DownloadController;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Zend\Http\Header\HeaderInterface;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class DownloadControllerTest extends AbstractControllerTest
{
    /**
     * @var DownloadController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(DownloadController::class);
    }

    public function testIndexActionNoPdfAvailable()
    {
        $this->setPdfType($this->lpa, 'lpa120');
        $this->logger->shouldReceive('info')->withArgs(['PDF not available', ['lpaId' => $this->lpa->id]])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionInQueue()
    {
        $pdfType = 'lp1';
        $this->setPdfType($this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'in-queue'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is in-queue', ['lpaId' => $this->lpa->id]])->once();

        $result = $this->controller->indexAction();

        $this->assertFalse($result);
    }

    public function testIndexActionLp1Ready()
    {
        $response = new Response();

        $pdfType = 'lp1';
        $this->setPdfType($this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download/file', [
            'lpa-id'       => $this->lpa->id,
            'pdf-type'     => $pdfType,
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP1F.pdf',
        ]])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionLp3Ready()
    {
        $this->lpa->document->peopleToNotify = [
            new NotifiedPerson(),
        ];

        $response = new Response();

        $pdfType = 'lp3';
        $this->setPdfType($this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download/file', [
            'lpa-id'       => $this->lpa->id,
            'pdf-type'     => $pdfType,
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP3.pdf',
        ]])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testDownloadActionInQueue()
    {
        $response = new Response();

        $pdfType = 'lp1';
        $routeMatch = $this->getRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'in-queue'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download', [
            'lpa-id'   => $this->lpa->id,
            'pdf-type' => $pdfType
        ]])->andReturn($response)->once();

        $this->logger->shouldReceive('info')->withArgs(['PDF status is in-queue', ['lpaId' => $this->lpa->id]])->once();

        $result = $this->controller->downloadAction();

        $this->assertEquals($response, $result);
    }

    public function testDownloadActionReady()
    {
        $response = Mockery::mock(Response::class);
        $this->controller->dispatch($this->request, $response);

        $pdfType = 'lp1';
        $routeMatch = $this->getRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType . '.pdf'])->andReturn('PDF content')->once();
        $response->shouldReceive('setContent')->withArgs(['PDF content'])->once();
        $headers = Mockery::mock(Headers::class);
        $response->shouldReceive('getHeaders')->andReturn($headers)->once();
        $headers->shouldReceive('clearHeaders')->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')
            ->withArgs(['Content-Type', 'application/pdf'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')
            ->withArgs(['Content-Disposition', 'inline; filename="Lasting-Power-of-Attorney-LP1F.pdf"'])
            ->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Content-Transfer-Encoding', 'Binary'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Content-Description', 'File Transfer'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Pragma', 'public'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Expires', '0'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Cache-Control', 'must-revalidate'])->andReturn($headers)->once();
        $headers->shouldReceive('addHeaderLine')->withArgs(['Content-Length', 11])->andReturn($headers)->once();

        $userAgentHeader = Mockery::mock(HeaderInterface::class);
        $userAgentHeader->shouldReceive('getFieldValue')->andReturn('');
        $headers->shouldReceive('get')->andReturn($userAgentHeader);
        $this->request->shouldReceive('getHeaders')->andReturn($headers);

        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();

        $result = $this->controller->downloadAction();

        $this->assertEquals($response, $result);
    }

    private function setPdfType($lpa, $pdfType)
    {
        $routeMatch = $this->getRouteMatch($this->controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->logger->shouldReceive('info')->withArgs(["PDF type is $pdfType", ['lpaId' => $lpa->id]])->once();
    }
}
