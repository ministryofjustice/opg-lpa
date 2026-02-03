<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\ForgotPasswordHandler;
use Application\Model\Service\User\Details as UserService;
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
            ->with('Application\Form\User\ConfirmEmail')
            ->willReturn($this->form);

        $this->handler = new ForgotPasswordHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
        );
    }

    public function testGetRequestDisplaysForm(): void
    {
        $request = (new ServerRequest())->withMethod('GET');

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/forgot-password');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/index.twig',
                $this->callback(function ($params) {
                    return isset($params['form']) && $params['error'] === null;
                })
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserIsRedirected(): void
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute('identity', ['userId' => '123']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testPostValidEmailSendsResetEmail(): void
    {
        $email = 'user@example.com';

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['email' => $email]);

        $this->form->expects($this->once())
            ->method('setData')
            ->with(['email' => $email]);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn(['email' => $email]);

        $this->userService
            ->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with($email)
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/email-sent.twig',
                $this->callback(function ($params) use ($email) {
                    return $params['email'] === $email
                        && $params['accountNotActivated'] === false;
                })
            )
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostNonExistentEmailStillShowsConfirmation(): void
    {
        $email = 'nonexistent@example.com';

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['email' => $email]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => $email]);

        $this->userService
            ->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with($email)
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/email-sent.twig',
                $this->callback(function ($params) use ($email) {
                    return $params['email'] === $email
                        && $params['accountNotActivated'] === false;
                })
            )
            ->willReturn('<html>email sent</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostAccountNotActivatedShowsSpecialMessage(): void
    {
        $email = 'inactive@example.com';

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['email' => $email]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => $email]);

        $this->userService
            ->expects($this->once())
            ->method('requestPasswordResetEmail')
            ->with($email)
            ->willReturn('account-not-activated');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/email-sent.twig',
                $this->callback(function ($params) {
                    return $params['accountNotActivated'] === true;
                })
            )
            ->willReturn('<html>account not activated</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidFormRedisplaysForm(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['email' => 'invalid-email']);

        $this->form->expects($this->once())
            ->method('setData')
            ->with(['email' => 'invalid-email']);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->userService
            ->expects($this->never())
            ->method('requestPasswordResetEmail');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/index.twig', $this->anything())
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithEmptyBodyHandledSafely(): void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
