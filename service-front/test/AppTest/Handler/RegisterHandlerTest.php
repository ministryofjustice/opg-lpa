<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\RegisterHandler;
use App\Form\User\Registration;
use App\Form\User\ConfirmEmail;
use App\Service\UserDetails as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class RegisterHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private LoggerInterface&MockObject $logger;
    private RegisterHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new RegisterHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
            $this->logger,
        );
    }

    public function testRedirectsFromGovUkReferer(): void
    {
        $request = (new ServerRequest(
            [],
            [],
            '/signup',
            'GET',
            'php://memory',
            ['Referer' => 'https://www.gov.uk/some-page'],
        ))->withAttribute('identity', null);

        $response = $this->handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testRedirectsAuthenticatedUserToDashboard(): void
    {
        $identity = new \stdClass();
        $identity->id = 'user123';

        $request = (new ServerRequest([], [], '/signup', 'GET'))
            ->withAttribute('identity', $identity);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Authenticated user attempted to access registration page');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testDisplaysRegistrationFormOnGetRequest(): void
    {
        $request = (new ServerRequest([], [], '/signup', 'GET'))
            ->withAttribute('identity', null);

        $form = $this->createMock(Registration::class);
        $form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/signup');

        $this->formElementManager
            ->expects($this->once())
            ->method('get')
            ->with(Registration::class)
            ->willReturn($form);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/index.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['form'] === $form
            ))
            ->willReturn('<html>Registration Form</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulRegistrationDisplaysEmailSentPage(): void
    {
        $request = (new ServerRequest([], [], '/signup', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody([
                'email'            => 'test@example.com',
                'email_confirm'    => 'test@example.com',
                'password'         => 'SecurePass123!',
                'password_confirm' => 'SecurePass123!',
                'terms'            => '1',
            ]);

        $registrationForm = $this->createMock(Registration::class);
        $registrationForm->method('setAttribute')->with('action', '/signup');
        $registrationForm->method('setData')->with($request->getParsedBody());
        $registrationForm->method('isValid')->willReturn(true);
        $registrationForm->method('getData')->willReturn([
            'email'    => 'test@example.com',
            'password' => 'SecurePass123!',
        ]);

        $resendForm = $this->createMock(ConfirmEmail::class);
        $resendForm->expects($this->once())->method('setAttribute')->with('action', '/signup/resend-email');
        $resendForm->expects($this->once())->method('setData')->with([
            'email'         => 'test@example.com',
            'email_confirm' => 'test@example.com',
        ]);

        $this->formElementManager->method('get')->willReturnCallback(
            function ($formClass) use ($registrationForm, $resendForm) {
                return match ($formClass) {
                    Registration::class  => $registrationForm,
                    ConfirmEmail::class  => $resendForm,
                    default              => null,
                };
            }
        );

        $this->userService->method('registerAccount')->with('test@example.com', 'SecurePass123!')->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/email-sent.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['form'] === $resendForm
                    && isset($data['email']) && $data['email'] === 'test@example.com'
            ))
            ->willReturn('<html>Email Sent</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testAlreadyRegisteredEmailAlsoDisplaysEmailSentPage(): void
    {
        $request = (new ServerRequest([], [], '/signup', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'test@example.com', 'password' => 'SecurePass123!']);

        $registrationForm = $this->createMock(Registration::class);
        $registrationForm->method('isValid')->willReturn(true);
        $registrationForm->method('getData')->willReturn(['email' => 'test@example.com', 'password' => 'SecurePass123!']);

        $resendForm = $this->createMock(ConfirmEmail::class);

        $this->formElementManager->method('get')->willReturnCallback(
            fn($formClass) => match ($formClass) {
                Registration::class  => $registrationForm,
                ConfirmEmail::class  => $resendForm,
                default              => null,
            }
        );

        $this->userService->method('registerAccount')->willReturn('address-already-registered');

        $this->renderer->method('render')
            ->with('application/general/register/email-sent.twig', $this->anything())
            ->willReturn('<html>Email Sent</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedRegistrationDisplaysError(): void
    {
        $request = (new ServerRequest([], [], '/signup', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'test@example.com', 'password' => 'SecurePass123!']);

        $form = $this->createMock(Registration::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['email' => 'test@example.com', 'password' => 'SecurePass123!']);

        $this->formElementManager->method('get')->with(Registration::class)->willReturn($form);
        $this->userService->method('registerAccount')->willReturn('api-error');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/index.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['form'] === $form
                    && isset($data['error']) && $data['error'] === 'api-error'
            ))
            ->willReturn('<html>Error</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidFormDataDisplaysFormWithErrors(): void
    {
        $request = (new ServerRequest([], [], '/signup', 'POST'))
            ->withAttribute('identity', null)
            ->withParsedBody(['email' => 'invalid-email', 'password' => 'short']);

        $form = $this->createMock(Registration::class);
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->with(Registration::class)->willReturn($form);

        $this->renderer->method('render')
            ->with('application/general/register/index.twig', $this->callback(
                fn($data) => isset($data['form']) && $data['form'] === $form
            ))
            ->willReturn('<html>Form with Errors</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }
}
