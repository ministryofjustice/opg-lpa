<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\ResendActivationEmailHandler;
use Application\Form\User\ConfirmEmail;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ResendActivationEmailHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private ResendActivationEmailHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);

        $this->handler = new ResendActivationEmailHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService
        );
    }

    public function testRedirectsAuthenticatedUserToDashboard(): void
    {
        $identity = new \stdClass();
        $identity->id = 'user123';

        $request = (new ServerRequest([], [], '/signup/resend-email', 'GET'))
            ->withAttribute('identity', $identity);

        $response = $this->handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testDisplaysResendEmailFormOnGetRequest(): void
    {
        $request = (new ServerRequest([], [], '/signup/resend-email', 'GET'))
            ->withAttribute('identity', null);

        $form = $this->createMock(ConfirmEmail::class);
        $form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup/resend-email');

        $this->formElementManager
            ->expects($this->once())
            ->method('get')
            ->with(ConfirmEmail::class)
            ->willReturn($form);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/resend-email.twig', $this->callback(function ($data) use ($form) {
                return isset($data['form']) && $data['form'] === $form;
            }))
            ->willReturn('<html>Resend Form</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulResendDisplaysEmailSentPage(): void
    {
        $request = (new ServerRequest([], [], '/signup/resend-email', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody([
                'email' => 'test@example.com',
                'email_confirm' => 'test@example.com',
            ]);

        $confirmForm = $this->createMock(ConfirmEmail::class);
        $confirmForm->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup/resend-email');
        $confirmForm->expects($this->once())
            ->method('setData')
            ->with($request->getParsedBody());
        $confirmForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $confirmForm->expects($this->once())
            ->method('getData')
            ->willReturn([
                'email' => 'test@example.com',
            ]);

        $resendForm = $this->createMock(ConfirmEmail::class);
        $resendForm->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup/resend-email');
        $resendForm->expects($this->once())
            ->method('setData')
            ->with([
                'email' => 'test@example.com',
                'email_confirm' => 'test@example.com',
            ]);

        $this->formElementManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with(ConfirmEmail::class)
            ->willReturnOnConsecutiveCalls($confirmForm, $resendForm);

        $this->userService
            ->expects($this->once())
            ->method('resendActivateEmail')
            ->with('test@example.com')
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/email-sent.twig', $this->callback(function ($data) use ($resendForm) {
                return isset($data['form']) && $data['form'] === $resendForm
                    && isset($data['email']) && $data['email'] === 'test@example.com';
            }))
            ->willReturn('<html>Email Sent</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedResendDisplaysError(): void
    {
        $request = (new ServerRequest([], [], '/signup/resend-email', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody([
                'email' => 'test@example.com',
                'email_confirm' => 'test@example.com',
            ]);

        $form = $this->createMock(ConfirmEmail::class);
        $form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup/resend-email');
        $form->expects($this->once())
            ->method('setData')
            ->with($request->getParsedBody());
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn([
                'email' => 'test@example.com',
            ]);

        $this->formElementManager
            ->expects($this->once())
            ->method('get')
            ->with(ConfirmEmail::class)
            ->willReturn($form);

        $this->userService
            ->expects($this->once())
            ->method('resendActivateEmail')
            ->with('test@example.com')
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/resend-email.twig', $this->callback(function ($data) use ($form) {
                return isset($data['form']) && $data['form'] === $form
                    && isset($data['error']) && $data['error'] === false;
            }))
            ->willReturn('<html>Error</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidFormDataDisplaysFormWithErrors(): void
    {
        $request = (new ServerRequest([], [], '/signup/resend-email', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody([
                'email' => 'invalid-email',
                'email_confirm' => 'different@example.com',
            ]);

        $form = $this->createMock(ConfirmEmail::class);
        $form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup/resend-email');
        $form->expects($this->once())
            ->method('setData')
            ->with($request->getParsedBody());
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->formElementManager
            ->expects($this->once())
            ->method('get')
            ->with(ConfirmEmail::class)
            ->willReturn($form);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/resend-email.twig', $this->callback(function ($data) use ($form) {
                return isset($data['form']) && $data['form'] === $form;
            }))
            ->willReturn('<html>Form with Errors</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }
}
