<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReplacementAttorneyDeleteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private ReplacementAttorneyDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyDeleteHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->replacementAttorneyCleanup,
        );
    }

    private function createLpa(): Lpa
    {
        return FixturesData::getPfLpa();
    }

    private function createRequest(?Lpa $lpa = null, mixed $idx = 0): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => $lpa->id, 'idx' => $idx]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest(null, -1));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testThrowsExceptionWhenApiCallFails(): void
    {
        $this->lpaApplicationService->method('deleteReplacementAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete replacement attorney');

        $this->handler->handle($this->createRequest(null, 0));
    }

    public function testDeleteSuccessRedirectsToIndex(): void
    {
        $this->lpaApplicationService->method('deleteReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');

        $response = $this->handler->handle($this->createRequest(null, 0));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('replacement-attorney', $response->getHeaderLine('Location'));
    }

    public function testDeleteDoesNotDeleteCorrespondentWhenNoneIsSet(): void
    {
        $lpa = $this->createLpa();
        $lpa->document->correspondent = null;

        $this->lpaApplicationService->expects($this->never())->method('deleteCorrespondent');
        $this->lpaApplicationService->method('deleteReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp');

        $response = $this->handler->handle($this->createRequest($lpa, 0));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
