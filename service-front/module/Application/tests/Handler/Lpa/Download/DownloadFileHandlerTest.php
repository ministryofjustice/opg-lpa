<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\Download;

use Application\Handler\Lpa\Download\DownloadFileHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Middleware\StubMiddleware;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DownloadFileHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private DownloadFileHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new DownloadFileHandler(
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->logger,
        );
    }

    private function createRequest(
        Lpa $lpa,
        string $pdfType,
        string $userAgent = ''
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);

        $route = new Route(
            '/lpa/:lpa-id/download/:pdf-type/:pdf-filename',
            new StubMiddleware(),
            null,
            'lpa/download/file'
        );
        $routeResult = RouteResult::fromRoute($route, [
            'lpa-id' => $lpa->id,
            'pdf-type' => $pdfType,
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP1F.pdf',
        ]);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/download/file')
            ->withAttribute(RouteResult::class, $routeResult);

        if ($userAgent !== '') {
            $request = $request->withHeader('User-Agent', $userAgent);
        }

        return $request;
    }

    public function testRedirectsToIndexWhenPdfNotReady(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'in-queue']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/download', [
                'lpa-id' => $lpa->id,
                'pdf-type' => 'lp1',
            ])
            ->willReturn('/lpa/' . $lpa->id . '/download/lp1');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectsWhenPdfContentsEmpty(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn('');

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectsWhenPdfContentsFalse(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn(false);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testReturnsPdfResponseWhenReady(): void
    {
        $lpa = FixturesData::getPfLpa();
        $pdfContent = 'PDF binary content here';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn($pdfContent);

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('Binary', $response->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertEquals('File Transfer', $response->getHeaderLine('Content-Description'));
        $this->assertEquals('public', $response->getHeaderLine('Pragma'));
        $this->assertEquals('0', $response->getHeaderLine('Expires'));
        $this->assertEquals('must-revalidate', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals((string) strlen($pdfContent), $response->getHeaderLine('Content-Length'));
        $this->assertStringContainsString('inline', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('LP1F.pdf', $response->getHeaderLine('Content-Disposition'));

        $response->getBody()->rewind();
        $this->assertEquals($pdfContent, $response->getBody()->getContents());
    }

    public function testReturnsAttachmentDispositionForEdgeBrowser(): void
    {
        $lpa = FixturesData::getPfLpa();
        $pdfContent = 'PDF binary content';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn($pdfContent);

        $response = $this->handler->handle(
            $this->createRequest($lpa, 'lp1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Edge/18.18363')
        );

        $this->assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
    }

    public function testReturnsInlineDispositionForNonEdgeBrowser(): void
    {
        $lpa = FixturesData::getPfLpa();
        $pdfContent = 'PDF binary content';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn($pdfContent);

        $response = $this->handler->handle(
            $this->createRequest($lpa, 'lp1', 'Mozilla/5.0 Chrome/100.0')
        );

        $this->assertStringContainsString('inline', $response->getHeaderLine('Content-Disposition'));
    }

    public function testReturnsCorrectFilenameForHwLpa(): void
    {
        $lpa = FixturesData::getHwLpa();
        $pdfContent = 'PDF binary content';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn($pdfContent);

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertStringContainsString('LP1H.pdf', $response->getHeaderLine('Content-Disposition'));
    }

    public function testReturnsCorrectFilenameForLp3(): void
    {
        $lpa = FixturesData::getPfLpa();
        $pdfContent = 'PDF binary content';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp3')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp3')
            ->willReturn($pdfContent);

        $route = new Route(
            '/lpa/:lpa-id/download/:pdf-type/:pdf-filename',
            new StubMiddleware(),
            null,
            'lpa/download/file'
        );
        $routeResult = RouteResult::fromRoute($route, [
            'lpa-id' => $lpa->id,
            'pdf-type' => 'lp3',
            'pdf-filename' => 'Lasting-Power-of-Attorney-LP3.pdf',
        ]);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $this->createMock(FormFlowChecker::class))
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/download/file')
            ->withAttribute(RouteResult::class, $routeResult);

        $response = $this->handler->handle($request);

        $this->assertStringContainsString('LP3.pdf', $response->getHeaderLine('Content-Disposition'));
    }

    public function testReturnsCorrectFilenameForDraftLpa(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(null);
        $pdfContent = 'PDF binary content';

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->lpaApplicationService->method('getPdfContents')
            ->with($lpa->id, 'lp1')
            ->willReturn($pdfContent);

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertStringContainsString('Draft-Lasting-Power-of-Attorney-LP1F.pdf', $response->getHeaderLine('Content-Disposition'));
    }
}
