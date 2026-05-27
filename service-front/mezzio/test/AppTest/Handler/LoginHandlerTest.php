<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LoginHandler;
use App\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use DateTime;
use Laminas\Authentication\Result;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private SessionInterface&MockObject $session;
    private FormInterface&MockObject $form;
    private LoginHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\Login')
            ->willReturn($this->form);

        $this->handler = new LoginHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
        );
    }

    private function createRequestWithSession(string $method = 'GET', ?array $parsedBody = null, ?string $state = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }

        if ($state !== null) {
            $request = $request->withAttribute('state', $state);
        }

        return $request;
    }

    public function testGetRequestDisplaysLoginForm(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/login');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return isset($params['form'])
                        && $params['authError'] === null
                        && $params['isTimeout'] === false
                        && $params['isInternalSystemError'] === false;
                })
            )
            ->willReturn('<html>login form</html>');

        $response = $this->handler->handle($this->createRequestWithSession());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserIsRedirectedToDashboard(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);

        $response = $this->handler->handle($this->createRequestWithSession());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testTimeoutStateIsPassedToTemplate(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['isTimeout'] === true
                        && $params['isInternalSystemError'] === false;
                })
            )
            ->willReturn('<html>login form</html>');

        $response = $this->handler->handle($this->createRequestWithSession(state: 'timeout'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInternalSystemErrorStateIsPassedToTemplate(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['isTimeout'] === false
                        && $params['isInternalSystemError'] === true;
                })
            )
            ->willReturn('<html>login form</html>');

        $response = $this->handler->handle($this->createRequestWithSession(state: 'internalSystemError'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testNoStateAttributeHandledSafely(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['isTimeout'] === false
                        && $params['isInternalSystemError'] === false;
                })
            )
            ->willReturn('<html>login form</html>');

        $response = $this->handler->handle($this->createRequestWithSession());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulLoginRegeneratesSessionAndStoresIdentity(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->session->method('get')->with('pre_auth_request_url')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('id')->willReturn('user-123');
        $identity->method('token')->willReturn('test-token');
        $identity->method('tokenExpiresAt')->willReturn(new DateTime('2026-06-01T00:00:00+00:00'));
        $identity->method('lastLogin')->willReturn(new DateTime('2026-05-20T10:00:00+00:00'));

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())
            ->method('set')
            ->with('identity', $this->callback(function ($data) {
                return $data['userId'] === 'user-123'
                    && $data['token'] === 'test-token'
                    && isset($data['tokenExpiresAt'])
                    && isset($data['lastLogin']);
            }));

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginRedirectsToPreAuthUrl(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->session->method('get')->with('pre_auth_request_url')->willReturn('/some/stored/url');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('id')->willReturn('user-123');
        $identity->method('token')->willReturn('test-token');
        $identity->method('tokenExpiresAt')->willReturn(new DateTime());
        $identity->method('lastLogin')->willReturn(new DateTime());

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->session->method('regenerate')->willReturn($this->session);

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/some/stored/url', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginWithInactivityFlagsStoresFlashWarning(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->session->method('get')->with('pre_auth_request_url')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('id')->willReturn('user-123');
        $identity->method('token')->willReturn('test-token');
        $identity->method('tokenExpiresAt')->willReturn(new DateTime());
        $identity->method('lastLogin')->willReturn(new DateTime());

        $result = new Result(Result::SUCCESS, $identity, ['inactivity-flags-cleared']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $setCalls = [];
        $this->session->method('regenerate')->willReturn($this->session);
        $this->session->method('set')->willReturnCallback(function ($key, $value) use (&$setCalls) {
            $setCalls[$key] = $value;
        });

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertArrayHasKey('flash_warning', $setCalls);
        $this->assertContains(
            'Thanks for logging in. Your LPA account will stay open for another 9 months.',
            $setCalls['flash_warning']
        );
    }

    public function testWarningNotShownWhenRedirectingToPreAuthUrl(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->session->method('get')->with('pre_auth_request_url')->willReturn('/stored/url');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('id')->willReturn('user-123');
        $identity->method('token')->willReturn('test-token');
        $identity->method('tokenExpiresAt')->willReturn(new DateTime());
        $identity->method('lastLogin')->willReturn(new DateTime());

        $result = new Result(Result::SUCCESS, $identity, ['inactivity-flags-cleared']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $setCalls = [];
        $this->session->method('regenerate')->willReturn($this->session);
        $this->session->method('set')->willReturnCallback(function ($key, $value) use (&$setCalls) {
            $setCalls[$key] = $value;
        });

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $response = $this->handler->handle($request);

        $this->assertEquals('/stored/url', $response->getHeaderLine('Location'));
        $this->assertArrayNotHasKey('flash_warning', $setCalls);
    }

    public function testSessionNotRegeneratedOnFailedLogin(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid credentials']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->session->expects($this->never())->method('regenerate');

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $this->handler->handle($request);
    }

    public function testFailedLoginDisplaysError(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid credentials']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['authError'] === 'Invalid credentials';
                })
            )
            ->willReturn('<html>login form with error</html>');

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedLoginPreservesEmailAddress(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'user@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->form
            ->expects($this->exactly(2))
            ->method('setData')
            ->willReturnCallback(function ($data) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 2) {
                    $this->assertEquals(['email' => 'user@example.com'], $data);
                }
            });

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = $this->createRequestWithSession('POST', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $this->handler->handle($request);
    }

    public function testInvalidFormDoesNotCallAuthService(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(false);

        $this->authenticationService
            ->expects($this->never())
            ->method('authenticate');

        $this->renderer->method('render')->willReturn('<html>login form</html>');

        $request = $this->createRequestWithSession('POST', ['email' => 'invalid']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithNullParsedBodyHandledSafely(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withParsedBody(null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testMultipleErrorMessagesReturnsLastOne(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE, null, ['First error', 'Second error', 'Last error']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['authError'] === 'Last error';
                })
            )
            ->willReturn('<html></html>');

        $request = $this->createRequestWithSession('POST', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $this->handler->handle($request);
    }
}
