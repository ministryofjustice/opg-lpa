<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\ResetPasswordHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResetPasswordHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private StorageInterface&MockObject $sessionStorage;
    private FlashMessenger&MockObject $flashMessenger;
    private FormInterface&MockObject $form;
    private ResetPasswordHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->sessionStorage = $this->createMock(StorageInterface::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);
        $this->sessionManager->method('getStorage')->willReturn($this->sessionStorage);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\SetPassword')
            ->willReturn($this->form);

        $this->handler = new ResetPasswordHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->flashMessenger,
        );
    }

    private function createRequestWithToken(?string $token, string $method = 'GET'): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')
            ->willReturn($token !== null ? ['token' => $token] : []);

        return (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testGetWithoutTokenShowsInvalidTokenPage(): void
    {
        $request = $this->createRequestWithToken(null);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid token</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertStringContainsString('invalid token', (string)$response->getBody());
    }

    public function testGetWithEmptyTokenShowsInvalidTokenPage(): void
    {
        $request = $this->createRequestWithToken('');

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid token</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithValidTokenDisplaysForm(): void
    {
        $token = 'valid-reset-token-123';
        $request = $this->createRequestWithToken($token);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/forgot-password/reset/' . $token);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/reset-password.twig',
                $this->callback(function ($params) {
                    return isset($params['form']) && $params['error'] === null;
                })
            )
            ->willReturn('<html>reset form</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserIsLoggedOutAndRedirected(): void
    {
        $token = 'valid-token';
        $request = $this->createRequestWithToken($token);

        $this->authenticationService
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn(['userId' => '123']);

        $this->sessionStorage
            ->expects($this->once())
            ->method('clear');

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('initialise');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/forgot-password/reset/' . $token, $response->getHeaderLine('Location'));
    }

    public function testPostValidPasswordResetsSuccessfully(): void
    {
        $token = 'valid-token';
        $newPass = 'TestPass123'; // pragma: allowlist secret

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['token' => $token]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['password' => $newPass, 'password_confirm' => $newPass])
            ->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with(['password' => $newPass, 'password_confirm' => $newPass]);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn(['password' => $newPass]);

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with($token, $newPass)
            ->willReturn(true);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with('Password successfully reset');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testPostInvalidTokenShowsInvalidTokenPage(): void
    {
        $token = 'expired-or-invalid-token';
        $newPass = 'TestPass123'; // pragma: allowlist secret

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['token' => $token]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['password' => $newPass])// pragma: allowlist secret
            ->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => $newPass]);

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with($token, $newPass)
            ->willReturn('invalid-token');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid token</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostServiceErrorDisplaysError(): void
    {
        $token = 'valid-token';
        $newPass = 'TestPass123'; // pragma: allowlist secret
        $errorMessage = 'Password does not meet requirements';

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['token' => $token]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['password' => $newPass])
            ->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['password' => $newPass]);

        $this->userService
            ->expects($this->once())
            ->method('setNewPassword')
            ->with($token, $newPass)
            ->willReturn($errorMessage);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/forgot-password/reset-password.twig',
                $this->callback(function ($params) use ($errorMessage) {
                    return $params['error'] === $errorMessage;
                })
            )
            ->willReturn('<html>form with error</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidFormRedisplaysForm(): void
    {
        $token = 'valid-token';

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['token' => $token]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['password' => 'weak'])//pragma: allowlist secret
            ->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with(['password' => 'weak']);//pragma: allowlist secret

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->userService
            ->expects($this->never())
            ->method('setNewPassword');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/reset-password.twig', $this->anything())
            ->willReturn('<html>form with validation errors</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithEmptyBodyHandledSafely(): void
    {
        $token = 'valid-token';

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['token' => $token]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(null)
            ->withAttribute(RouteResult::class, $routeResult);

        $this->authenticationService->method('getIdentity')->willReturn(null);

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

    public function testNoRouteResultHandledSafely(): void
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteResult::class, null);

        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/forgot-password/invalid-reset-token.twig')
            ->willReturn('<html>invalid token</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
