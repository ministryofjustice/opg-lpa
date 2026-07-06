<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\ConfirmDeleteLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfirmDeleteLpaHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private ConfirmDeleteLpaHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->handler = new ConfirmDeleteLpaHandler(
            $this->renderer,
            $this->lpaApplicationService,
        );
    }

    private function createRequest(bool $isXhr = false): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => '123']);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteResult::class, $routeResult)
            ->withQueryParams(['page' => '2']);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        return $request;
    }

    public function testRendersConfirmDeleteTemplateWithLpaAndPage(): void
    {
        $lpa = new Lpa(['id' => 123]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getApplication')
            ->with('123')
            ->willReturn($lpa);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(fn(array $params): bool => $params['lpa'] === $lpa
                    && $params['page'] === '2'
                    && !isset($params['isPopup']))
            )
            ->willReturn('<html>confirm delete</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSetsIsPopupWhenRequestIsXhr(): void
    {
        $lpa = new Lpa(['id' => 123]);

        $this->lpaApplicationService->method('getApplication')->willReturn($lpa);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(fn(array $params): bool => ($params['isPopup'] ?? false) === true)
            )
            ->willReturn('<html>confirm delete popup</html>');

        $response = $this->handler->handle($this->createRequest(true));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
