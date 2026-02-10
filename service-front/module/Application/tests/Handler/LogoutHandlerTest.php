<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\LogoutHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private array $config;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);

        $this->config = [
            'redirects' => ['logout' => 'https://www.gov.uk'],
        ];
    }

    public function testClearsIdentityOnLogout(): void
    {
        $handler = new LogoutHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->config,
        );

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $request = new ServerRequest();
        $handler->handle($request);
    }

    public function testDestroysSessionOnLogout(): void
    {
        $handler = new LogoutHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->config,
        );

        $this->sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $request = new ServerRequest();
        $handler->handle($request);
    }

    public function testRedirectsToConfiguredLogoutUrl(): void
    {
        $handler = new LogoutHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->config,
        );

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://www.gov.uk', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToRootWhenConfigMissing(): void
    {
        $handler = new LogoutHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
            [],
        );

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testResponseIs302Redirect(): void
    {
        $handler = new LogoutHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
            $this->config,
        );

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
    }
}
