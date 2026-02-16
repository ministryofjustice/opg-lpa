<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\DeletedAccountHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SessionManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletedAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private DeletedAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);

        $this->handler = new DeletedAccountHandler(
            $this->renderer,
            $this->authenticationService,
            $this->sessionManagerSupport,
        );
    }

    public function testClearsIdentity(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->renderer->method('render')->willReturn('<html>deleted</html>');

        $request = new ServerRequest();
        $this->handler->handle($request);
    }

    public function testDestroysSession(): void
    {
        $this->sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $this->renderer->method('render')->willReturn('<html>deleted</html>');

        $request = new ServerRequest();
        $this->handler->handle($request);
    }

    public function testRendersDeletedTemplate(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/deleted.twig')
            ->willReturn('<html>deleted</html>');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testReturns200StatusCode(): void
    {
        $this->renderer->method('render')->willReturn('<html>deleted</html>');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturnsHtmlContent(): void
    {
        $expectedHtml = '<html><body>Your account has been deleted</body></html>';

        $this->renderer
            ->method('render')
            ->willReturn($expectedHtml);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertEquals($expectedHtml, (string)$response->getBody());
    }
}
