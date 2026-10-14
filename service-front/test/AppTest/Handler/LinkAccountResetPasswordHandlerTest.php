<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\ConfirmEmail;
use App\Handler\LinkAccountResetPasswordHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\UserDetails as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LinkAccountResetPasswordHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private UserService&MockObject $userService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private FormInterface&MockObject $form;
    private LinkAccountResetPasswordHandler $handler;

    protected function setUp(): void
    {
        $this->renderer    = $this->createMock(TemplateRendererInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->session     = $this->createMock(SessionInterface::class);
        $this->form        = $this->createMock(FormInterface::class);

        $formElementManager = $this->createMock(FormElementManager::class);
        $formElementManager->method('get')->with(ConfirmEmail::class)->willReturn($this->form);

        $this->handler = new LinkAccountResetPasswordHandler(
            $this->renderer,
            $formElementManager,
            $this->userService,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    private function createRequest(string $method = 'GET', array $postData = [], bool $onboarding = true): ServerRequest
    {
        $pendingLink = $onboarding
            ? ['sub' => 'urn:fdc:gov.uk:2022:newuser', 'email' => 'newuser@example.com', 'idToken' => 'pending.id.token']
            : null;

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => $key === 'onelogin_pending_link' ? $pendingLink : null);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/link-account/reset-password'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        return $method === 'POST' ? $request->withParsedBody($postData) : $request;
    }

    public function testUserNotOnboardingIsAskedToSignInAgain(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.onboarding_missing_pending_link');
        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest(onboarding: false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/onboarding/reset-password.twig',
                $this->callback(fn(array $vars) => $vars['form'] === $this->form && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>form</html>');

        $this->assertInstanceOf(HtmlResponse::class, $this->handler->handle($this->createRequest()));
    }

    public function testInvalidPostRendersFormAgain(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->userService->expects($this->never())->method('requestPasswordResetEmail');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/onboarding/reset-password.twig', $this->anything())
            ->willReturn('<html>form</html>');

        $this->assertInstanceOf(
            HtmlResponse::class,
            $this->handler->handle($this->createRequest('POST', ['email' => ''])),
        );
    }

    public function testValidPostEmailsTheLink(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'test@example.com']);

        $this->userService->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with('test@example.com')
            ->willReturn(true);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/email-sent.twig',
                ['email' => 'test@example.com', 'accountNotActivated' => false],
            )
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($this->createRequest(
            'POST',
            ['email' => 'test@example.com', 'email_confirm' => 'test@example.com'],
        ));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
