<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginSignInHandler;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OneLoginSignInHandlerTest extends TestCase
{
    private OneLoginService&MockObject $oneLoginService;
    private SessionInterface&MockObject $session;
    private OneLoginSignInHandler $handler;
    private RedirectUriBuilder $redirectUriBuilder;

    protected function setUp(): void
    {
        $this->oneLoginService    = $this->createMock(OneLoginService::class);
        $this->session            = $this->createMock(SessionInterface::class);
        $this->redirectUriBuilder = new RedirectUriBuilder();
        $this->handler            = new OneLoginSignInHandler($this->oneLoginService, $this->redirectUriBuilder);
    }

    private function buildRequest(string $scheme = 'https', string $host = 'localhost:7002'): ServerRequest
    {
        return (new ServerRequest())
            ->withUri(new Uri($scheme . '://' . $host . '/auth/onelogin'))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testAlreadySignedInUserIsRedirectedWithoutStartingAuthentication(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);

        $this->oneLoginService->expects($this->never())->method('start');
        $this->session->expects($this->never())->method('set');

        $response = $this->handler->handle($this->buildRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testHandleRedirectsToServiceUrl(): void
    {
        $authUrl = 'https://auth.example.com/authorize?state=abc&nonce=def';

        $this->oneLoginService
            ->expects($this->once())
            ->method('start')
            ->willReturn(['state' => 'abc', 'nonce' => 'def', 'codeVerifier' => 'ghi', 'url' => $authUrl]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('onelogin_auth', $this->callback(function (array $data): bool {
                return $data['state'] === 'abc'
                    && $data['nonce'] === 'def'
                    && str_ends_with($data['redirect_uri'], '/auth/redirect');
            }));

        $response = $this->handler->handle($this->buildRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($authUrl, $response->getHeaderLine('Location'));
    }

    public function testHandlePassesCorrectRedirectUriDerivedFromRequest(): void
    {
        $capturedRedirectUri = null;

        $this->oneLoginService
            ->method('start')
            ->willReturnCallback(function (string $redirectUri) use (&$capturedRedirectUri) {
                $capturedRedirectUri = $redirectUri;
                return ['state' => 'x', 'nonce' => 'y', 'codeVerifier' => 'z', 'url' => 'https://auth.example.com'];
            });

        $this->session->method('set');

        $this->handler->handle($this->buildRequest('https', 'localhost:7002'));

        $this->assertSame('https://localhost:7002/auth/redirect', $capturedRedirectUri);
    }

    public function testRedirectUriIsPersistentInSession(): void
    {
        $this->oneLoginService
            ->method('start')
            ->willReturn(['state' => 'x', 'nonce' => 'y', 'codeVerifier' => 'z', 'url' => 'https://auth.example.com']);

        $sessionData = null;
        $this->session
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$sessionData): void {
                if ($key === 'onelogin_auth') {
                    $sessionData = $value;
                }
            });

        $this->handler->handle($this->buildRequest('https', 'myservice.example.com'));

        $this->assertIsArray($sessionData);
        $this->assertSame('https://myservice.example.com/auth/redirect', $sessionData['redirect_uri']);
    }

    public function testSessionReceivesExactStateNonceAndCodeVerifierUnderOneloginAuthKey(): void
    {
        $state        = 'state-abc-123';
        $nonce        = 'nonce-xyz-456';
        $codeVerifier = 'verifier-789';

        $this->oneLoginService
            ->method('start')
            ->willReturn([
                'state'        => $state,
                'nonce'        => $nonce,
                'codeVerifier' => $codeVerifier,
                'url'          => 'https://auth.example.com',
            ]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('onelogin_auth', $this->callback(function (array $data) use ($state, $nonce, $codeVerifier): bool {
                return $data['state'] === $state
                    && $data['nonce'] === $nonce
                    && $data['code_verifier'] === $codeVerifier;
            }));

        $this->handler->handle($this->buildRequest());
    }

    public function testConfiguredBaseUrlOverridesRequestUri(): void
    {
        $builder = new RedirectUriBuilder('https://production.example.gov.uk');
        $handler = new OneLoginSignInHandler($this->oneLoginService, $builder);

        $capturedRedirectUri = null;
        $this->oneLoginService
            ->method('start')
            ->willReturnCallback(function (string $redirectUri) use (&$capturedRedirectUri) {
                $capturedRedirectUri = $redirectUri;
                return ['state' => 'x', 'nonce' => 'y', 'codeVerifier' => 'z', 'url' => 'https://auth.example.com'];
            });

        $this->session->method('set');

        $handler->handle($this->buildRequest('http', 'localhost:7002'));

        $this->assertSame('https://production.example.gov.uk/auth/redirect', $capturedRedirectUri);
    }
}
