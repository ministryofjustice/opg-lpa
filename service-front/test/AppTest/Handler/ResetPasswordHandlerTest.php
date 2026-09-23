<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\ResetPasswordHandler;
use App\Form\User\SetPassword;
use App\Service\UserDetails as UserService;
use App\View\Twig\FlashMessenger;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResetPasswordHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private SessionInterface&MockObject $session;
    private FlashMessagesInterface&MockObject $flash;
    private FormInterface&MockObject $form;
    private ResetPasswordHandler $handler;
    private string|false $originalOneLoginEnabled;

    protected function setUp(): void
    {
        $this->originalOneLoginEnabled = getenv('ONELOGIN_ENABLED');
        putenv('ONELOGIN_ENABLED=false');

        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->flash = $this->createMock(FlashMessagesInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with(SetPassword::class)
            ->willReturn($this->form);

        $this->handler = new ResetPasswordHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
        );
    }

    protected function tearDown(): void
    {
        if ($this->originalOneLoginEnabled === false) {
            putenv('ONELOGIN_ENABLED');
        } else {
            putenv('ONELOGIN_ENABLED=' . $this->originalOneLoginEnabled);
        }

        parent::tearDown();
    }

    private function createRequest(string $method = 'GET', ?string $token = 'abc123', ?array $parsedBody = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash)
            ->withAttribute('token', $token);

        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }

    public function testInvalidTokenFormatShowsInvalidTokenPage(): void
    {
        $this->session->method('has')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid</html>');

        $response = $this->handler->handle($this->createRequest(token: 'invalid!@#'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    #[DataProvider('mangledTokens')]
    public function testMangledTokenShowsInvalidTokenPage(string $token): void
    {
        $this->session->method('has')->willReturn(false);

        $this->userService->expects($this->never())->method('setNewPassword');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid</html>');

        $response = $this->handler->handle($this->createRequest(token: $token));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame([], $response->getHeader('Location'));
    }

    /** @return array<string, array{string}> */
    public static function mangledTokens(): array
    {
        return [
            'trailing full stop'  => ['aaaaaaaaaaaaaaaaaaaa.'],
            'trailing bracket'    => ['aaaaaaaaaaaaaaaaaaaa>'],
            'trailing comma'      => ['aaaaaaaaaaaaaaaaaaaa,'],
            'wrapped in brackets' => ['<aaaaaaaaaaaaaaaaaaaa>'],
            'trailing whitespace' => ['aaaaaaaaaaaaaaaaaaaa '],
            'trailing slash'      => ['aaaaaaaaaaaaaaaaaaaa/'],
            'extra path segment'  => ['aaaaaaaaaaaaaaaaaaaa/extra'],
            'crlf injection'      => ["aaaaaaaaaaaaaaaaaaaa\r\nSet-Cookie: evil=1"],
        ];
    }

    public function testEmptyTokenShowsInvalidTokenPage(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid</html>');

        $response = $this->handler->handle($this->createRequest(token: ''));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testLoggedInUserSessionIsClearedAndRedirected(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $response = $this->handler->handle($this->createRequest(token: 'validtoken'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/forgot-password/reset/validtoken', $response->getHeaderLine('Location'));
    }

    public function testLoggedInUserSessionIsKeptWhenForSharedSpace(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/reset-password.twig')
            ->willReturn('<html>hey</html>');

        $response = $this->handler->handle($this->createRequest(token: 'sharedspacevalidtoken'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRequestDisplaysForm(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/reset-password.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['error'] === null
            ))
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulResetRedirectsToLoginWithFlash(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => 'NewPass123!']); // pragma: allowlist secret

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with('abc123', 'NewPass123!')
            ->willReturn(true);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::SUCCESS, ['Password successfully reset']);

        $request = $this->createRequest('POST', 'abc123', ['password' => 'NewPass123!', 'password_confirm' => 'NewPass123!']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulResetRedirectsToHomeWhenOneLoginEnabled(): void
    {
        putenv('ONELOGIN_ENABLED=true');

        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => 'NewPass123!']); // pragma: allowlist secret

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with('abc123', 'NewPass123!')
            ->willReturn(true);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::SUCCESS, ['Password successfully reset']);

        $request = $this->createRequest('POST', 'abc123', ['password' => 'NewPass123!', 'password_confirm' => 'NewPass123!']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/home', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulResetRedirectsToDashboardWhenSharedSpaceWithSession(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => 'NewPass123!']); // pragma: allowlist secret

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with('sharedspaceabc123', 'NewPass123!')
            ->willReturn(true);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::SUCCESS, ['Password successfully reset']);

        $request = $this->createRequest('POST', 'sharedspaceabc123', ['password' => 'NewPass123!', 'password_confirm' => 'NewPass123!']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/shared-space/dashboard', $response->getHeaderLine('Location'));
    }

    public function testInvalidTokenResultShowsInvalidTokenPage(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => 'NewPass123!']); // pragma: allowlist secret

        $this->userService->method('setNewPassword')->willReturn('invalid-token');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid</html>');

        $request = $this->createRequest('POST', 'abc123', ['password' => 'NewPass123!']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testOtherErrorDisplaysFormWithError(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => 'NewPass123!']); // pragma: allowlist secret

        $this->userService->method('setNewPassword')->willReturn('some-error');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/reset-password.twig', $this->callback(
                fn($data) => $data['error'] === 'some-error'
            ))
            ->willReturn('<html>error</html>');

        $request = $this->createRequest('POST', 'abc123', ['password' => 'NewPass123!']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidFormRedisplaysForm(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/reset-password.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['error'] === null
            ))
            ->willReturn('<html>form</html>');

        $request = $this->createRequest('POST', 'abc123', ['password' => 'short']); // pragma: allowlist secret
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
