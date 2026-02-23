<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Form\User\ChangePassword as ChangePasswordForm;
use Application\Handler\ChangePasswordHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangePasswordHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private UserService&MockObject $userService;
    private FlashMessenger&MockObject $flashMessenger;
    private ChangePasswordForm&MockObject $form;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->form = $this->createMock(ChangePasswordForm::class);

        $this->formElementManager
            ->method('get')
            ->with(ChangePasswordForm::class)
            ->willReturn($this->form);

        $this->handler = new ChangePasswordHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->userService,
            $this->flashMessenger,
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
            ->withAttribute('userDetails', $user)
            ->withAttribute('secondsUntilSessionExpires', 3600);
    }

    public function testGetRequestDisplaysForm(): void
    {
        $user = $this->createUserWithEmail('current@example.com');

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/change-password');

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
                'application/authenticated/change-password/index.twig',
                $this->callback(function ($params) use ($user) {
                    return $params['form'] === $this->form
                        && $params['error'] === null
                        && $params['pageTitle'] === 'Change your password'
                        && $params['cancelUrl'] === '/user/about-you'
                        && $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html>change password form</html>');

        $request = $this->createAuthenticatedRequest($user);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostValidDataUpdatesPasswordAndRedirects(): void
    {
        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'password_current' => 'oldPassword123', // pragma: allowlist secret
            'password' => 'newPassword456', // pragma: allowlist secret
        ]);

        $this->userService
            ->expects($this->once())
            ->method('updatePassword')
            ->with('oldPassword123', 'newPassword456') // pragma: allowlist secret
            ->willReturn(true);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with(
                'Your new password has been saved. ' .
                'Please remember to use this new password to sign in from now on.'
            );

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody([
                'password_current' => 'oldPassword123', // pragma: allowlist secret
                'password' => 'newPassword456', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/about-you', $response->getHeaderLine('Location'));
    }

    public function testPostValidDataWithApiErrorShowsError(): void
    {
        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'password_current' => 'oldPassword123', // pragma: allowlist secret
            'password' => 'newPassword456', // pragma: allowlist secret
        ]);

        $this->userService
            ->expects($this->once())
            ->method('updatePassword') // pragma: allowlist secret
            ->with('oldPassword123', 'newPassword456') // pragma: allowlist secret
            ->willReturn('Current password is incorrect');

        $this->flashMessenger
            ->expects($this->never())
            ->method('addSuccessMessage');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-password/index.twig',
                $this->callback(function ($params) {
                    return $params['error'] === 'Current password is incorrect'
                        && isset($params['form']);
                })
            )
            ->willReturn('<html>form with error</html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody([
                'password_current' => 'oldPassword123', // pragma: allowlist secret
                'password' => 'newPassword456', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidDataRedisplaysForm(): void
    {
        $user = $this->createUserWithEmail('current@example.com');

        $this->form->method('isValid')->willReturn(false);

        $this->userService
            ->expects($this->never())
            ->method('updatePassword');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/change-password/index.twig',
                $this->callback(function ($params) {
                    return isset($params['form'])
                        && $params['error'] === null;
                })
            )
            ->willReturn('<html>form with validation errors</html>');

        $request = $this->createAuthenticatedRequest($user)
            ->withMethod('POST')
            ->withParsedBody(['password' => 'weak']);  // pragma: allowlist secret

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithNullParsedBodyHandledSafely(): void
    {
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
        $user = $this->createUserWithEmail();

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/change-password');

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user);
        $this->handler->handle($request);
    }

    public function testTemplateReceivesCommonVariables(): void
    {
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

    public function testTemplateReceivesPageTitleAndCancelUrl(): void
    {
        $user = $this->createUserWithEmail();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function ($params) {
                    return $params['pageTitle'] === 'Change your password'
                        && $params['cancelUrl'] === '/user/about-you';
                })
            )
            ->willReturn('<html></html>');

        $request = $this->createAuthenticatedRequest($user);
        $this->handler->handle($request);
    }
}
