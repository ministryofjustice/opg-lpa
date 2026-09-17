<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeSharedTest\DataModel\FixturesData;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReplacementAttorneyConfirmDeleteHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private TemplateRendererInterface&MockObject $renderer;
    private UrlHelper&MockObject $urlHelper;
    private ReplacementAttorneyConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyConfirmDeleteHandler(
            $this->lpaApplicationService,
            $this->replacementAttorneyCleanup,
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpa(bool $withTrust = false): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        if ($withTrust) {
            $lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();
        }
        return $lpa;
    }

    /**
     * @psalm-param int<-1, max> $idx
     */
    private function createRequest(
        string $method,
        Lpa $lpa,
        int $idx = 0,
        bool $isXhr = false,
        array $postData = [],
    ): ServerRequest {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => $lpa->id, 'idx' => $idx]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RouteResult::class, $routeResult);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest('GET', $this->createLpa(), -1));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRendersConfirmDeleteForHumanAttorney(): void
    {
        $lpa = $this->createLpa();
        $idx = 0;
        $attorney = $lpa->document->replacementAttorneys[$idx];

        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/confirm-delete.twig',
                $this->callback(function (array $vars) use ($attorney): bool {
                    $this->assertEquals($attorney->name, $vars['attorneyName']);
                    $this->assertFalse($vars['isTrust']);
                    $this->assertEquals('/lpa/91333263035/lpa/replacement-attorney/confirm-delete', $vars['actionUrl']);
                    $this->assertEquals('/lpa/91333263035/lpa/replacement-attorney', $vars['cancelUrl']);
                    $this->assertFalse($vars['isPopup']);
                    return true;
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $lpa, $idx));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersConfirmDeleteForTrustAttorney(): void
    {
        $lpa = $this->createLpa(true);
        $trustIdx = count($lpa->document->replacementAttorneys) - 1;

        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/confirm-delete.twig',
                $this->callback(fn(array $vars) => $vars['isTrust'] === true)
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $lpa, $trustIdx));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersWithIsPopupWhenXhr(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                $this->anything(),
                $this->callback(fn(array $vars) => $vars['isPopup'] === true)
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $this->createLpa(), 0, true));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersWithoutIsPopupWhenNotXhr(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                $this->anything(),
                $this->callback(fn(array $vars) => $vars['isPopup'] === false)
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $this->createLpa(), 0, false));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testThrowsExceptionWhenApiCallFails(): void
    {
        $this->lpaApplicationService->method('deleteReplacementAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete replacement attorney');

        $this->handler->handle($this->createRequest('POST', $this->createLpa(), 0, postData: ['version' => '5']));
    }

    public function testDeleteSuccessRedirectsToIndex(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService->method('deleteReplacementAttorney')->with($lpa, 1, 5)->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp')->with($lpa, 6);

        $response = $this->handler->handle($this->createRequest('POST', $lpa, 0, postData: ['version' => '5']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('replacement-attorney', $response->getHeaderLine('Location'));
    }
}
