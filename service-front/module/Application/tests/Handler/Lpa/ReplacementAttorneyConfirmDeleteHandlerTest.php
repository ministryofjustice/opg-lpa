<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplacementAttorneyConfirmDeleteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private ReplacementAttorneyConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyConfirmDeleteHandler(
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

    private function createRequest(
        ?Lpa $lpa = null,
        mixed $idx = 0,
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => $lpa->id, 'idx' => $idx]);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RouteResult::class, $routeResult);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        return $request;
    }

    public function testInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest(null, -1));

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
                    return $vars['attorneyName'] === $attorney->name
                        && $vars['isTrust'] === false
                        && isset($vars['deleteRoute'])
                        && isset($vars['cancelUrl'])
                        && !isset($vars['isPopup']);
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest($lpa, $idx));

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

        $response = $this->handler->handle($this->createRequest($lpa, $trustIdx));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersWithIsPopupWhenXhr(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                $this->anything(),
                $this->callback(fn(array $vars) => ($vars['isPopup'] ?? false) === true)
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest(null, 0, true));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersWithoutIsPopupWhenNotXhr(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                $this->anything(),
                $this->callback(fn(array $vars) => !isset($vars['isPopup']))
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest(null, 0, false));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
