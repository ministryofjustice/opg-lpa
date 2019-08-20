<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DownloadController;
use Application\Model\Service\Analytics\GoogleAnalyticsService;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Exception;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Zend\Http\Header\HeaderInterface;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class DownloadControllerTest extends AbstractControllerTest
{
    public function testIndexActionNoPdfAvailable()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $this->setPdfType($controller, $this->lpa, 'lpa120');
        $this->logger->shouldReceive('info')->withArgs(['PDF not available', ['lpaId' => $this->lpa->id]])->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionInQueue()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $pdfType = 'lp1';
        $this->setPdfType($controller, $this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'in-queue'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is in-queue', ['lpaId' => $this->lpa->id]])->once();

        $result = $controller->indexAction();

        $this->assertFalse($result);
    }

    public function testIndexActionLp1Ready()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $response = new Response();

        $pdfType = 'lp1';
        $this->setPdfType($controller, $this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download/check', [
            'lpa-id'       => $this->lpa->id,
            'pdf-type'     => $pdfType,
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP1F.pdf',
        ]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionDraftLp1Ready()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $response = new Response();

        // Remove payment so that the lpa is incomplete
        $this->lpa->setPayment(null);

        $pdfType = 'lp1';
        $this->setPdfType($controller, $this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download/check', [
            'lpa-id'       => $this->lpa->id,
            'pdf-type'     => $pdfType,
            'pdf-filename' => 'Draft-Lasting-Power-of-Attorney-LP1F.pdf',
        ]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionLp3Ready()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $this->lpa->document->peopleToNotify = [
            new NotifiedPerson(),
        ];

        $response = new Response();

        $pdfType = 'lp3';
        $this->setPdfType($controller, $this->lpa, $pdfType);
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->layout->shouldReceive('__invoke')->withArgs(['layout/download.twig'])->once();
        $this->logger->shouldReceive('info')->withArgs(['PDF status is ready', ['lpaId' => $this->lpa->id]])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download/check', [
            'lpa-id'       => $this->lpa->id,
            'pdf-type'     => $pdfType,
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP3.pdf',
        ]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testDownloadActionInQueue()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $response = new Response();

        $pdfType = 'lp1';
        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'in-queue'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['lpa/download', [
            'lpa-id'   => $this->lpa->id,
            'pdf-type' => $pdfType
        ]])->andReturn($response)->once();

        $this->logger->shouldReceive('info')->withArgs(['PDF status is in-queue', ['lpaId' => $this->lpa->id]])->once();

        $result = $controller->downloadAction();

        $this->assertEquals($response, $result);
    }

    public function testDownloadActionReady()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $googleAnalyticsService = Mockery::mock(GoogleAnalyticsService::class);
        $controller->setAnalyticsService($googleAnalyticsService);

        $response = Mockery::mock(Response::class);
        $controller->dispatch($this->request, $response);

        $pdfType = 'lp1';
        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->lpaApplicationService->shouldReceive('getPdfContents')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn('PDF content')->once();
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

        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('getHost')->andReturn('Test Host')->once();
        $uri->shouldReceive('getPath')->andReturn('/test-path')->once();

        $this->request->shouldReceive('getUri')->andReturn($uri);
        $googleAnalyticsService->shouldReceive('sendPageView')
            ->withArgs(['Test Host', '/test-path', 'Lasting-Power-of-Attorney-LP1F.pdf'])->once();

        $result = $controller->downloadAction();

        $this->assertEquals($response, $result);
    }

    public function testDownloadActionReadyWithAnalyticsException()
    {
        /** @var DownloadController $controller */
        $controller = $this->getController(DownloadController::class);

        $googleAnalyticsService = Mockery::mock(GoogleAnalyticsService::class);
        $controller->setAnalyticsService($googleAnalyticsService);

        $response = Mockery::mock(Response::class);
        $controller->dispatch($this->request, $response);

        $pdfType = 'lp1';
        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->lpaApplicationService->shouldReceive('getPdf')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn(['status' => 'ready'])->once();
        $this->lpaApplicationService->shouldReceive('getPdfContents')
            ->withArgs([$this->lpa->id, $pdfType])->andReturn('PDF content')->once();
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

        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('getHost')->andReturn('Test Host')->once();
        $uri->shouldReceive('getPath')->andReturn('/test-path')->once();

        $exception = new Exception('Test Exception');

        $this->request->shouldReceive('getUri')->andReturn($uri);
        $googleAnalyticsService->shouldReceive('sendPageView')
            ->withArgs(['Test Host', '/test-path', 'Lasting-Power-of-Attorney-LP1F.pdf'])
            ->andThrowExceptions([$exception]);

        $this->logger->shouldReceive('err')->withArgs([$exception]);

        $result = $controller->downloadAction();

        $this->assertEquals($response, $result);
    }

    private function setPdfType($controller, $lpa, $pdfType)
    {
        $routeMatch = $this->getRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['pdf-type'])->andReturn($pdfType)->once();
        $this->logger->shouldReceive('info')->withArgs(["PDF type is $pdfType", ['lpaId' => $lpa->id]])->once();
    }
}
