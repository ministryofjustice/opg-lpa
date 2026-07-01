<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\ForgotPasswordHandler;
use App\Form\User\ConfirmEmail;
use App\Service\UserDetails as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ForgotPasswordHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private FormInterface&MockObject $form;
    private ForgotPasswordHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with(ConfirmEmail::class)
            ->willReturn($this->form);

        $this->handler = new ForgotPasswordHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
        );
    }

    public function testAuthenticatedUserIsRedirectedToDashboard(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'GET'))
            ->withAttribute('identity', new \stdClass());

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testGetRequestDisplaysForm(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'GET'))
            ->withAttribute('identity', null);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/forgot-password');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/index.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['error'] === null
            ))
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testValidPostSendsResetEmailAndDisplaysConfirmation(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'test@example.com', 'email_confirm' => 'test@example.com']);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'test@example.com']);

        $this->userService
            ->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with('test@example.com')
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/email-sent.twig', $this->callback(
                fn($data) => $data['email'] === 'test@example.com' && $data['accountNotActivated'] === false
            ))
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testAccountNotActivatedFlagIsPassedToTemplate(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'test@example.com', 'email_confirm' => 'test@example.com']);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'test@example.com']);

        $this->userService->method('requestPasswordResetEmail')->willReturn('account-not-activated');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/email-sent.twig', $this->callback(
                fn($data) => $data['accountNotActivated'] === true
            ))
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidFormRedisplaysForm(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => '']);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/index.twig', $this->callback(
                fn($data) => isset($data['form'])
            ))
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostNonExistentEmailStillShowsEmailSentPage(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'nonexistent@example.com', 'email_confirm' => 'nonexistent@example.com']);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'nonexistent@example.com']);

        $this->userService
            ->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with('nonexistent@example.com')
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/email-sent.twig',
                $this->callback(fn($data) => $data['email'] === 'nonexistent@example.com'
                    && $data['accountNotActivated'] === false)
            )
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithEmptyBodyHandledSafely(): void
    {
        $request = (new ServerRequest([], [], '/forgot-password', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->expects($this->once())
            ->method('render')
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
