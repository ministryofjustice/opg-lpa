<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\CreateLpaHandler;
use App\Service\Lpa\Application as LpaApplicationService;
use App\View\Twig\FlashMessenger;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateLpaHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private SessionInterface&MockObject $session;
    private FlashMessagesInterface&MockObject $flash;
    private CreateLpaHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->flash = $this->createMock(FlashMessagesInterface::class);

        $this->handler = new CreateLpaHandler($this->lpaApplicationService);
    }

    private function createRequest(?string $seedId = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash);

        if ($seedId !== null) {
            $request = $request->withAttribute('lpa-id', $seedId);
        }

        return $request;
    }

    public function testRedirectsToTypeWhenNoSeedId(): void
    {
        $this->lpaApplicationService->expects($this->never())->method('createApplication');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/type', $response->getHeaderLine('Location'));
    }

    public function testCreatesLpaFromSeedClearsCloneDataAndRedirects(): void
    {
        $lpa = new Lpa(['id' => 123]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn($lpa);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setSeed')
            ->with($lpa, 'seed-1')
            ->willReturn(true);

        $this->session->expects($this->once())->method('get')->with('clone_data')->willReturn([
            'seed-1' => ['name' => 'Original'],
            'other-seed' => ['name' => 'Other'],
        ]);
        $this->session->expects($this->once())->method('set')->with('clone_data', [
            'other-seed' => ['name' => 'Other'],
        ]);
        $this->flash->expects($this->never())->method('flash');

        $response = $this->handler->handle($this->createRequest('seed-1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/123/type', $response->getHeaderLine('Location'));
    }

    public function testFlashesErrorAndRedirectsToDashboardWhenCreateFails(): void
    {
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn(false);

        $this->lpaApplicationService->expects($this->never())->method('setSeed');
        $this->session->expects($this->never())->method('get');

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::ERROR, ['Error creating a new LPA. Please try again.']);

        $response = $this->handler->handle($this->createRequest('seed-1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testFlashesWarningWhenSetSeedFailsButStillRedirects(): void
    {
        $lpa = new Lpa(['id' => 456]);

        $this->lpaApplicationService->method('createApplication')->willReturn($lpa);
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setSeed')
            ->with($lpa, 'seed-1')
            ->willReturn(false);

        $this->session->expects($this->once())->method('get')->with('clone_data')->willReturn([
            'seed-1' => ['name' => 'Original'],
        ]);
        $this->session->expects($this->once())->method('set')->with('clone_data', []);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::WARNING, ['LPA created but could not set seed']);

        $response = $this->handler->handle($this->createRequest('seed-1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/456/type', $response->getHeaderLine('Location'));
    }
}
