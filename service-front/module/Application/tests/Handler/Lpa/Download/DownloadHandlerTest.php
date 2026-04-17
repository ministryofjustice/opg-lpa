<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\Download;

use Application\Handler\Lpa\Download\DownloadHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Middleware\StubMiddleware;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DownloadHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private DownloadHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer->method('render')->willReturn('html');

        $this->handler = new DownloadHandler(
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
            '/lpa/:lpa-id/download/:pdf-type',
            new StubMiddleware(),
            null,
            'lpa/download'
        );
        $routeResult = RouteResult::fromRoute($route, [
            'lpa-id' => $lpa->id,
            'pdf-type' => $pdfType,
        ]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/download')
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testIndexReturns404PageWhenLpa120NotAvailable(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('PDF type is lpa120', $this->anything());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('PDF not available', $this->anything());

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('error/404.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lpa120'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testIndexReturns404PageWhenLp3NotAvailable(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->peopleToNotify = [];

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('error/404.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp3'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testIndexRendersPollingPageWhenPdfInQueue(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'in-queue']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('layout/download.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testIndexRedirectsToCheckWhenLp1Ready(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with(
                'lpa/download/check',
                $this->callback(function (array $params) use ($lpa) {
                    return $params['lpa-id'] === $lpa->id
                        && $params['pdf-type'] === 'lp1'
                        && str_contains($params['pdf-filename'], 'LP1F.pdf');
                })
            )
            ->willReturn('/lpa/' . $lpa->id . '/download/lp1/check');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testIndexRedirectsWithDraftFilenameWhenLpaIncomplete(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(null);

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with(
                'lpa/download/check',
                $this->callback(function (array $params) {
                    return str_contains($params['pdf-filename'], 'Draft-Lasting-Power-of-Attorney-LP1F.pdf');
                })
            )
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testIndexRedirectsWithLp3FilenameWhenReady(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->peopleToNotify = [new NotifiedPerson()];

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp3')
            ->willReturn(['status' => 'ready']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with(
                'lpa/download/check',
                $this->callback(function (array $params) {
                    return $params['pdf-filename'] === 'Lasting-Power-of-Attorney-LP3.pdf';
                })
            )
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp3'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testIndexRedirectsWithHwFilenameForHealthAndWelfareLpa(): void
    {
        $lpa = FixturesData::getHwLpa();

        $this->lpaApplicationService->method('getPdf')
            ->with($lpa->id, 'lp1')
            ->willReturn(['status' => 'ready']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with(
                'lpa/download/check',
                $this->callback(function (array $params) {
                    return str_contains($params['pdf-filename'], 'LP1H.pdf');
                })
            )
            ->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest($lpa, 'lp1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @dataProvider pdfTypeNotAvailableProvider
     */
    public function testIndexReturns404WhenPdfTypeNotAvailable(
        string $pdfType,
        callable $lpaModifier
    ): void {
        $lpa = FixturesData::getPfLpa();
        $lpaModifier($lpa);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('error/404.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, $pdfType));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @return array<string, array{string, callable}>
     */
    public static function pdfTypeNotAvailableProvider(): array
    {
        return [
            'lpa120 not eligible' => [
                'lpa120',
                function (Lpa $lpa): void {
                    // Default PF LPA is completed but not eligible for fee reduction
                },
            ],
            'lp3 no people to notify' => [
                'lp3',
                function (Lpa $lpa): void {
                    $lpa->document->peopleToNotify = [];
                },
            ],
        ];
    }
}
