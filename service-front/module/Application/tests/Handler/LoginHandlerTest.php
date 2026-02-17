<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\LoginHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Authentication\Result;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private StorageInterface&MockObject $sessionStorage;
    private SessionUtility&MockObject $sessionUtility;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private FlashMessenger&MockObject $flashMessenger;
    private FormInterface&MockObject $form;
    private LoginHandler $handler;
    private array $config;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->sessionStorage = $this->createMock(StorageInterface::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);
        $this->sessionManager->method('getStorage')->willReturn($this->sessionStorage);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\Login')
            ->willReturn($this->form);

        $this->config = [
            'redirects' => ['logout' => '/'],
        ];

        $this->handler = new LoginHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->sessionUtility,
            $this->lpaApplicationService,
            $this->flashMessenger,
            $this->config,
        );
    }

    public function testGetRequestDisplaysLoginForm(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

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

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAuthenticatedUserIsRedirectedToDashboard(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testTimeoutStateIsPassedToTemplate(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $routeMatch = new RouteMatch(['state' => 'timeout']);
        $request = (new ServerRequest())
            ->withAttribute(RouteMatch::class, $routeMatch);

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

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInternalSystemErrorStateIsPassedToTemplate(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $routeMatch = new RouteMatch(['state' => 'internalSystemError']);
        $request = (new ServerRequest())
            ->withAttribute(RouteMatch::class, $routeMatch);

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

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testNoRouteMatchHandledSafely(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

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

        $request = (new ServerRequest())
            ->withAttribute(RouteMatch::class, null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUnknownStateIsIgnored(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $routeMatch = new RouteMatch(['state' => 'unknownState']);
        $request = (new ServerRequest())
            ->withAttribute(RouteMatch::class, $routeMatch);

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

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulLoginRedirectsToDashboard(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->willReturn(null);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginRedirectsToStoredUrl(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->willReturn('/some/stored/url');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/some/stored/url', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginRedirectsToLpaDateCheckUrl(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->willReturn('/lpa/12345/date-check');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/12345/date-check', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginRedirectsToLpaFormFlowRoute(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->willReturn('/lpa/12345/some-page');

        $lpa = new Lpa();
        $lpa->id = 12345;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getApplication')
            ->with(12345, 'test-token')
            ->willReturn($lpa);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('/lpa/12345/', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulLoginWithLpaUrlButNoLpaFoundRedirectsToStoredUrl(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with(ContainerNamespace::PRE_AUTH_REQUEST, 'url')
            ->willReturn('/lpa/99999/some-page');

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getApplication')
            ->with(99999, 'test-token')
            ->willReturn(false);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/lpa/99999/some-page', $response->getHeaderLine('Location'));
    }

    public function testSessionIsClearedBeforeAuthentication(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE, null, []);
        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionStorage
            ->expects($this->once())
            ->method('clear');

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('initialise');

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testSessionIdIsRegeneratedAfterSuccessfulLogin(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility->method('getFromMvc')->willReturn(null);

        $this->sessionManager
            ->expects($this->once())
            ->method('regenerateId')
            ->with(true);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testSessionIdIsNotRegeneratedOnFailedLogin(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid credentials']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionManager
            ->expects($this->never())
            ->method('regenerateId');

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'wrongpassword', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testFailedLoginDisplaysError(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

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

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'wrongpassword', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedLoginPreservesEmailAddress(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

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

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'user@example.com',
                'password' => 'wrongpassword', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testMultipleErrorMessagesReturnsLastOne(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

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

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'wrongpassword', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testEmptyErrorMessagesArrayPassedToTemplate(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // pragma: allowlist secret
        ]);

        $result = new Result(Result::FAILURE, null, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/auth/index.twig',
                $this->callback(function ($params) {
                    return $params['authError'] === [];
                })
            )
            ->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'wrongpassword', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testInvalidFormRedisplaysFormWithoutCallingAuthService(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(false);

        $this->authenticationService
            ->expects($this->never())
            ->method('authenticate');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html>login form</html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['email' => 'invalid']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithNullParsedBodyHandledSafely(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithEmptyArrayHandledSafely(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->expects($this->once())
            ->method('setData')
            ->with([]);

        $this->form->method('isValid')->willReturn(false);

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInactivityFlagsClearedShowsWarningMessage(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, ['inactivity-flags-cleared']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility->method('getFromMvc')->willReturn(null);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addWarningMessage')
            ->with('Thanks for logging in. Your LPA account will stay open for another 9 months.');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testNoWarningMessageWhenInactivityFlagsNotCleared(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility->method('getFromMvc')->willReturn(null);

        $this->flashMessenger
            ->expects($this->never())
            ->method('addWarningMessage');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $this->handler->handle($request);
    }

    public function testWarningMessageNotShownWhenRedirectingToStoredUrl(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'email' => 'test@example.com',
            'password' => 'password123', // pragma: allowlist secret
        ]);

        $identity = $this->createMock(UserIdentity::class);
        $identity->method('token')->willReturn('test-token');

        $result = new Result(Result::SUCCESS, $identity, ['inactivity-flags-cleared']);

        $this->authenticationService->method('setEmail')->willReturnSelf();
        $this->authenticationService->method('setPassword')->willReturnSelf();
        $this->authenticationService->method('authenticate')->willReturn($result);

        $this->sessionUtility
            ->method('getFromMvc')
            ->willReturn('/some/stored/url');

        $this->flashMessenger
            ->expects($this->never())
            ->method('addWarningMessage');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertEquals('/some/stored/url', $response->getHeaderLine('Location'));
    }
}
