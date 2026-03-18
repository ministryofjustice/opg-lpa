<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Router\RouteMatch;
use MakeShared\DataModel\Lpa\Lpa;
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

    private function createRequest(array $routeParams = [], array $queryParams = [], array $headers = []): ServerRequest
    {
        $routeMatch = new RouteMatch($routeParams);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch)
            ->withQueryParams($queryParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    public function testRendersConfirmDeleteTemplate(): void
    {
        $lpaId = '99';
        $request = $this->createRequest(['lpa-id' => $lpaId], ['page' => '2']);

        $lpa = new Lpa();
        $lpa->id = (int) $lpaId;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getApplication')
            ->with($lpaId)
            ->willReturn($lpa);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(function (array $params) use ($lpa) {
                    return $params['lpa'] === $lpa
                        && $params['page'] === '2'
                        && !isset($params['isPopup']);
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSetsIsPopupForXhrRequest(): void
    {
        $request = $this->createRequest(
            ['lpa-id' => '99'],
            [],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $this->lpaApplicationService->method('getApplication')->willReturn(new Lpa());

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(function (array $params) {
                    return $params['isPopup'] === true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testDoesNotSetIsPopupForNonXhrRequest(): void
    {
        $request = $this->createRequest(['lpa-id' => '99']);

        $this->lpaApplicationService->method('getApplication')->willReturn(new Lpa());

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(function (array $params) {
                    return !isset($params['isPopup']);
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPageIsNullWhenNotProvided(): void
    {
        $request = $this->createRequest(['lpa-id' => '99']);

        $this->lpaApplicationService->method('getApplication')->willReturn(new Lpa());

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/confirm-delete.twig',
                $this->callback(function (array $params) {
                    return $params['page'] === null;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($request);
    }
}
