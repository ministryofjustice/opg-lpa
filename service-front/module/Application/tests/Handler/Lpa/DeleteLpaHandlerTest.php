<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteLpaHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private FlashMessenger&MockObject $flashMessenger;
    private DeleteLpaHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);

        $this->handler = new DeleteLpaHandler(
            $this->lpaApplicationService,
            $this->flashMessenger,
        );
    }

    private function createRequest(array $routeParams = [], array $queryParams = []): ServerRequest
    {
        $routeMatch = new RouteMatch($routeParams);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch)
            ->withQueryParams($queryParams);
    }

    public function testSuccessfulDeleteRedirectsToDashboard(): void
    {
        $request = $this->createRequest(['lpa-id' => '99']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteApplication')
            ->with('99')
            ->willReturn(true);

        $this->flashMessenger
            ->expects($this->never())
            ->method('addErrorMessage');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testFailedDeleteShowsErrorMessage(): void
    {
        $request = $this->createRequest(['lpa-id' => '99']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteApplication')
            ->with('99')
            ->willReturn(false);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('LPA could not be deleted');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToPaginationWhenPageProvided(): void
    {
        $request = $this->createRequest(['lpa-id' => '99'], ['page' => '3']);

        $this->lpaApplicationService->method('deleteApplication')->willReturn(true);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard/page/3', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToDashboardWhenPageIsNonNumeric(): void
    {
        $request = $this->createRequest(['lpa-id' => '99'], ['page' => 'abc']);

        $this->lpaApplicationService->method('deleteApplication')->willReturn(true);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }
}
