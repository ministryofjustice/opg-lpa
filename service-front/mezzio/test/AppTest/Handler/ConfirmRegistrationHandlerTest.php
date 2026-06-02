<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\ConfirmRegistrationHandler;
use App\Service\UserDetails as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfirmRegistrationHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private UserService&MockObject $userService;
    private ConfirmRegistrationHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->userService = $this->createMock(UserService::class);

        $this->handler = new ConfirmRegistrationHandler(
            $this->renderer,
            $this->userService,
        );
    }

    private function createRequest(string $token = ''): ServerRequest
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('clear');
        $session->method('regenerate');

        return (new ServerRequest([], [], '/signup/confirm/' . $token, 'GET'))
            ->withAttribute('token', $token ?: null)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
    }

    public function testMissingTokenDisplaysError(): void
    {
        $request = (new ServerRequest([], [], '/signup/confirm/'))
            ->withAttribute('token', null);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(
                fn($data) => isset($data['error']) && $data['error'] === 'invalid-token'
            ))
            ->willReturn('<html>Invalid Token</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testEmptyTokenDisplaysError(): void
    {
        $request = $this->createRequest('');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(
                fn($data) => isset($data['error']) && $data['error'] === 'invalid-token'
            ))
            ->willReturn('<html>Invalid Token</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulAccountActivation(): void
    {
        $token = 'valid-activation-token-123';
        $request = $this->createRequest($token);

        $this->userService
            ->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(
                fn($data) => !isset($data['error'])
            ))
            ->willReturn('<html>Account Activated</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedAccountActivation(): void
    {
        $token = 'invalid-token-123';
        $request = $this->createRequest($token);

        $this->userService
            ->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(
                fn($data) => isset($data['error']) && $data['error'] === 'account-missing'
            ))
            ->willReturn('<html>Account Not Found</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSessionIsClearedAndRegeneratedOnValidToken(): void
    {
        $token = 'valid-token-123';

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('clear');
        $session->expects($this->once())->method('regenerate');

        $request = (new ServerRequest([], [], '/signup/confirm/' . $token, 'GET'))
            ->withAttribute('token', $token)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $this->userService->method('activateAccount')->willReturn(true);
        $this->renderer->method('render')->willReturn('<html></html>');

        $this->handler->handle($request);
    }
}
