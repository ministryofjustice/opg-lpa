<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\DeleteLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use App\View\Twig\FlashMessenger;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Flash\FlashMessageMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteLpaHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MockObject $flash;
    private DeleteLpaHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->flash = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['flash'])
            ->getMock();

        $this->handler = new DeleteLpaHandler($this->lpaApplicationService);
    }

    private function createRequest(?string $page = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute('lpa-id', '123')
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash);

        if ($page !== null) {
            $request = $request->withQueryParams(['page' => $page]);
        }

        return $request;
    }

    public function testRedirectsToDashboardWhenDeleteSucceedsWithoutPage(): void
    {
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteApplication')
            ->with('123')
            ->willReturn(true);

        $this->flash->expects($this->never())->method('flash');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToDashboardPageWhenDeleteSucceedsWithPage(): void
    {
        $this->lpaApplicationService->method('deleteApplication')->willReturn(true);
        $this->flash->expects($this->never())->method('flash');

        $response = $this->handler->handle($this->createRequest('2'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard/page/2', $response->getHeaderLine('Location'));
    }

    public function testFlashesErrorAndRedirectsToDashboardWhenDeleteFails(): void
    {
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteApplication')
            ->with('123')
            ->willReturn(false);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::ERROR, ['LPA could not be deleted']);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }
}
