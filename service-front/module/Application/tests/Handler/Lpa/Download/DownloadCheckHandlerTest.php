<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\Download;

use Application\Handler\Lpa\Download\DownloadCheckHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Middleware\StubMiddleware;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DownloadCheckHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private DownloadCheckHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer->method('render')->willReturn('html');

        $this->handler = new DownloadCheckHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->logger,
        );
    }

    private function createRequest(Lpa $lpa, string $pdfType): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);

        $route = new Route(
            '/lpa/:lpa-id/download/:pdf-type/check',
            new StubMiddleware(),
            null,
            'lpa/download/check'
        );
        $routeResult = RouteResult::fromRoute($route, [
            'lpa-id' => $lpa->id,
            'pdf-type' => $pdfType,
        ]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/download/check')
            ->withAttribute(RouteResult::class, $routeResult);
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

    public function testRendersDownloadingPageWhenPdfReady(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'layout/downloading.twig',
                $this->callback(function (array $vars) use ($lpa) {
                    return $vars['data']['lpaid'] === $lpa->id
                        && $vars['data']['pdftype'] === 'lp1'
                        && str_contains($vars['data']['pdffilename'], 'LP1F.pdf');
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersDownloadingPageWithCorrectHwFilename(): void
    {
        $lpa = FixturesData::getHwLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'layout/downloading.twig',
                $this->callback(function (array $vars) {
                    return str_contains($vars['data']['pdffilename'], 'LP1H.pdf');
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersDownloadingPageWithDraftFilename(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(null);

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'layout/downloading.twig',
                $this->callback(function (array $vars) {
                    return str_starts_with($vars['data']['pdffilename'], 'Draft-');
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersDownloadingPageWithLp3Filename(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp3')
            ->willReturn(['status' => 'ready']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'layout/downloading.twig',
                $this->callback(function (array $vars) {
                    return $vars['data']['pdffilename'] === 'Lasting-Power-of-Attorney-LP3.pdf';
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp3'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRedirectsWhenPdfReturnsNonArrayFalsyResult(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(false);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
