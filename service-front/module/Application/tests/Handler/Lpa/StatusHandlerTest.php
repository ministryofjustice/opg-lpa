<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\StatusHandler;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private StatusHandler $handler;

    private array $config = [
        'processing-status' => [
            'track-from-date' => '2017-01-01',
            'expected-working-days-before-receipt' => 15,
        ],
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->renderer->method('render')->willReturn('html');

        $this->handler = new StatusHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $this->config,
        );
    }

    private function createRequest(?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? FixturesData::getPfLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/status');
    }

    public function testReturnsHtmlResponseForValidStatus(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersCorrectTemplate(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/status/index.twig', $this->anything())
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }

    public function testRedirectsToDashboardWhenLpaNotCompleted(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setCompletedAt(null);

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToDashboardForInvalidStatus(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'InvalidStatus']]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testReturnUnpaidStatusRendersHtml(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Processed', 'returnUnpaid' => true]]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testViewDataContainsCanGenerateLPA120(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) {
                    return array_key_exists('canGenerateLPA120', $vars);
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }

    #[DataProvider('statusProvider')]
    public function testValidStatusesReturnHtmlResponse(string $status): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => $status]]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function statusProvider(): array
    {
        return [
            ['waiting'],
            ['received'],
            ['checking'],
            ['processed'],
            ['completed'],
        ];
    }

    #[DataProvider('processedDateFixtureProvider')]
    public function testProcessedDateGeneration(array $dates, ?string $expectedDate): void
    {
        $expectedShouldReceiveByDate = null;
        if ($expectedDate !== null) {
            $expectedShouldReceiveByDate = new DateTime($expectedDate);
        }

        $testLpa = FixturesData::getPfLpa();
        $testLpa->setCompletedAt(new DateTime('2020-03-10'));
        $testLpa->setMetadata(array_merge($testLpa->getMetadata(), $dates));

        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['found' => true, 'status' => 'Processed']);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) use ($expectedShouldReceiveByDate) {
                    return $vars['shouldReceiveByDate'] == $expectedShouldReceiveByDate;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest($testLpa));
    }

    public static function processedDateFixtureProvider(): array
    {
        return [
            'no dates' => [
                [], null,
            ],
            'rejected date' => [
                ['application-rejected-date' => '2020-03-01'],
                '2020-03-20',
            ],
            'withdrawn date' => [
                ['application-withdrawn-date' => '2020-04-01'],
                '2020-04-22',
            ],
            'invalid date' => [
                ['application-invalid-date' => '2020-05-01'],
                '2020-05-22',
            ],
            'dispatch date' => [
                ['application-dispatch-date' => '2020-06-01'],
                '2020-06-22',
            ],
            'multiple dates uses latest' => [
                [
                    'application-invalid-date' => '2021-05-05',
                    'application-dispatch-date' => '2021-05-07',
                    'application-rejected-date' => '2021-05-06',
                    'application-withdrawn-date' => '2021-05-04',
                ],
                '2021-05-28',
            ],
        ];
    }

    public function testRedirectsToDashboardWhenConfigMissing(): void
    {
        $handler = new StatusHandler(
            $this->renderer,
            $this->lpaApplicationService,
            [],
        );

        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testViewDataContainsLpa(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) use ($lpa) {
                    return isset($vars['lpa']) && $vars['lpa']->id === $lpa->id;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testViewDataContainsStatus(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Checking']]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) {
                    return isset($vars['status']) && $vars['status'] === 'checking';
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }

    public function testViewDataContainsDoneStatuses(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Checking']]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) {
                    return isset($vars['doneStatuses'])
                        && is_array($vars['doneStatuses'])
                        && in_array('completed', $vars['doneStatuses'])
                        && in_array('waiting', $vars['doneStatuses'])
                        && in_array('received', $vars['doneStatuses']);
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }

    public function testViewDataContainsReturnUnpaidFlag(): void
    {
        $this->lpaApplicationService->method('getStatuses')
            ->willReturn(['91333263035' => ['found' => true, 'status' => 'Processed', 'returnUnpaid' => true]]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $vars) {
                    return isset($vars['returnUnpaid']) && $vars['returnUnpaid'] === true;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }
}
