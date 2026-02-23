<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\ConfirmRegistrationHandler;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Router\RouteMatch;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfirmRegistrationHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private UserService&MockObject $userService;
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private ConfirmRegistrationHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);

        $this->handler = new ConfirmRegistrationHandler(
            $this->renderer,
            $this->userService,
            $this->authenticationService,
            $this->sessionManagerSupport
        );
    }

    public function testSuccessfulAccountActivation(): void
    {
        $token = 'valid-activation-token-123';

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects($this->once())
            ->method('getParam')
            ->with('token')
            ->willReturn($token);

        $request = (new ServerRequest([], [], '/signup/confirm/' . $token, 'GET'))
            ->withAttribute(RouteMatch::class, $routeMatch);

        $sessionManager = $this->createMock(SessionManager::class);
        $storage = $this->createMock(ArrayStorage::class);
        $storage->expects($this->once())
            ->method('clear');

        $sessionManager->expects($this->once())
            ->method('getStorage')
            ->willReturn($storage);

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('getSessionManager')
            ->willReturn($sessionManager);

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('initialise');

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->userService
            ->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(true);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(function ($data) {
                return !isset($data['error']);
            }))
            ->willReturn('<html>Account Activated</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFailedAccountActivation(): void
    {
        $token = 'invalid-token-123';

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects($this->once())
            ->method('getParam')
            ->with('token')
            ->willReturn($token);

        $request = (new ServerRequest([], [], '/signup/confirm/' . $token, 'GET'))
            ->withAttribute(RouteMatch::class, $routeMatch);

        $sessionManager = $this->createMock(SessionManager::class);
        $storage = $this->createMock(ArrayStorage::class);
        $storage->expects($this->once())
            ->method('clear');

        $sessionManager->expects($this->once())
            ->method('getStorage')
            ->willReturn($storage);

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('getSessionManager')
            ->willReturn($sessionManager);

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('initialise');

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->userService
            ->expects($this->once())
            ->method('activateAccount')
            ->with($token)
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(function ($data) {
                return isset($data['error']) && $data['error'] === 'account-missing';
            }))
            ->willReturn('<html>Account Not Found</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testMissingTokenDisplaysError(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects($this->once())
            ->method('getParam')
            ->with('token')
            ->willReturn(null);

        $request = (new ServerRequest([], [], '/signup/confirm/', 'GET'))
            ->withAttribute(RouteMatch::class, $routeMatch);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(function ($data) {
                return isset($data['error']) && $data['error'] === 'invalid-token';
            }))
            ->willReturn('<html>Invalid Token</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testEmptyTokenDisplaysError(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects($this->once())
            ->method('getParam')
            ->with('token')
            ->willReturn('');

        $request = (new ServerRequest([], [], '/signup/confirm/', 'GET'))
            ->withAttribute(RouteMatch::class, $routeMatch);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/register/confirm.twig', $this->callback(function ($data) {
                return isset($data['error']) && $data['error'] === 'invalid-token';
            }))
            ->willReturn('<html>Invalid Token</html>');

        $response = $this->handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
    }
}
