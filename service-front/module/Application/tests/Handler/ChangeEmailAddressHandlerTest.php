<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Form\User\ChangeEmailAddress as ChangeEmailAddressForm;
use Application\Handler\ChangeEmailAddressHandler;
use Application\Listener\Attribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeEmailAddressHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private UserService&MockObject $userService;
    private ChangeEmailAddressForm&MockObject $form;
    private ChangeEmailAddressHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->form = $this->createMock(ChangeEmailAddressForm::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\ChangeEmailAddress')
            ->willReturn($this->form);

        $this->handler = new ChangeEmailAddressHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->userService,
        );
    }

    private function createUserWithEmail(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->email = new EmailAddress(['address' => $email]);
        return $user;
    }

    private function createAuthenticatedRequest(User $user): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute(Attribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 3600);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testGetRequestDisplaysForm(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('current@example.com');

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/change-email-address');

        $this->authenticationService
            ->expects($this->once())
            ->method('setEmail')
            ->with('current@example.com');

        $this->form->expects($this->once())
            ->method('setAuthenticationService')
            ->with($this->authenticationService);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-email-address/index.twig',
                $this->callback(function ($params) use ($user) {
                    return $params['form'] === $this->form
                        && $params['error'] === null
                        && $params['currentEmailAddress'] === 'current@example.com'
                        && $params['cancelUrl'] === '/user/about-you'
                        && $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html>change email form</html>');

        $request = $this->createAuthenticatedRequest($user);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostValidDataSendsEmailAndShowsConfirmation(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'new@example.com']);

        $this->userService
            ->expects($this->once())
            ->method('requestEmailUpdate')
            ->with('new@example.com', 'current@example.com')
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-email-address/email-sent.twig',
                $this->callback(function ($params) use ($user) {
                    return $params['email'] === 'new@example.com'
                        && $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html>email sent</html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody(['email' => 'new@example.com']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidDataWithApiErrorShowsError(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['email' => 'new@example.com']);

        $this->userService
            ->expects($this->once())
            ->method('requestEmailUpdate')
            ->with('new@example.com', 'current@example.com')
            ->willReturn('Email address already in use');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-email-address/index.twig',
                $this->callback(function ($params) {
                    return $params['error'] === 'Email address already in use'
                        && isset($params['form'])
                        && $params['currentEmailAddress'] === 'current@example.com';
                })
            )
            ->willReturn('<html>form with error</html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody(['email' => 'new@example.com']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidDataRedisplaysForm(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(false);

        $this->userService
            ->expects($this->never())
            ->method('requestEmailUpdate');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-email-address/index.twig',
                $this->callback(function ($params) {
                    return isset($params['form'])
                        && $params['error'] === null;
                })
            )
            ->willReturn('<html>form with validation errors</html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody(['email' => 'invalid']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithNullParsedBodyHandledSafely(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('current@example.com');

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody(null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormReceivesAuthenticationService(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail('test@example.com');

        $this->authenticationService
            ->expects($this->once())
            ->method('setEmail')
            ->with('test@example.com');

        $this->form
            ->expects($this->once())
            ->method('setAuthenticationService')
            ->with($this->authenticationService);

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user);
        $this->handler->handle($request);
    }

    public function testFormActionIsSetCorrectly(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail();

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/change-email-address');

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user);
        $this->handler->handle($request);
    }

    public function testTemplateReceivesCommonVariables(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function ($params) use ($user) {
                    return $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user);
        $this->handler->handle($request);
    }
}
