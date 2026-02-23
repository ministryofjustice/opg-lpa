<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\VerifyEmailAddressHandler;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerifyEmailAddressHandlerTest extends TestCase
{
    private UserService&MockObject $userService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private StorageInterface&MockObject $sessionStorage;
    private FlashMessenger&MockObject $flashMessenger;
    private VerifyEmailAddressHandler $handler;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->sessionStorage = $this->createMock(StorageInterface::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);
        $this->sessionManager->method('getStorage')->willReturn($this->sessionStorage);

        $this->handler = new VerifyEmailAddressHandler(
            $this->userService,
            $this->sessionManagerSupport,
            $this->flashMessenger,
        );
    }

    private function createRequestWithToken(?string $token): ServerRequest
    {
        $routeMatch = new RouteMatch(['token' => $token]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch);
    }

    public function testSessionIsClearedAndInitialised(): void
    {
        $request = $this->createRequestWithToken('valid-token');

        $this->sessionStorage
            ->expects($this->once())
            ->method('clear');

        $this->sessionManagerSupport
            ->expects($this->once())
            ->method('initialise');

        $this->userService->method('updateEmailUsingToken')->willReturn(true);

        $this->handler->handle($request);
    }

    public function testSuccessfulVerificationShowsSuccessMessage(): void
    {
        $token = 'valid-token-123';
        $request = $this->createRequestWithToken($token);

        $this->userService
            ->expects($this->once())
            ->method('updateEmailUsingToken')
            ->with($token)
            ->willReturn(true);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with('Your email address was successfully updated. Please login with your new address.');

        $this->flashMessenger
            ->expects($this->never())
            ->method('addErrorMessage');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testFailedVerificationShowsErrorMessage(): void
    {
        $token = 'invalid-token';
        $request = $this->createRequestWithToken($token);

        $this->userService
            ->expects($this->once())
            ->method('updateEmailUsingToken')
            ->with($token)
            ->willReturn(false);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('There was an error updating your email address');

        $this->flashMessenger
            ->expects($this->never())
            ->method('addSuccessMessage');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testNullTokenShowsError(): void
    {
        $routeMatch = new RouteMatch([]);
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RouteMatch::class, $routeMatch);

        $this->userService
            ->expects($this->never())
            ->method('updateEmailUsingToken');

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('There was an error updating your email address');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testEmptyTokenShowsError(): void
    {
        $request = $this->createRequestWithToken('');

        $this->userService
            ->expects($this->never())
            ->method('updateEmailUsingToken');

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('There was an error updating your email address');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }
}
