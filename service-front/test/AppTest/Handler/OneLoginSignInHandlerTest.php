<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginSignInHandler;
use App\Service\OneLogin\OneLoginService;
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

    protected function setUp(): void
    {
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->session         = $this->createMock(SessionInterface::class);
        $this->handler         = new OneLoginSignInHandler($this->oneLoginService);
    }

    private function buildRequest(string $scheme = 'https', string $host = 'localhost:7002'): ServerRequest
    {
        return (new ServerRequest())
            ->withUri(new Uri($scheme . '://' . $host . '/auth/onelogin'))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testHandleRedirectsToServiceUrl(): void
    {
        $authUrl = 'https://auth.example.com/authorize?state=abc&nonce=def';

        $this->oneLoginService
            ->expects($this->once())
            ->method('start')
            ->willReturn(['state' => 'abc', 'nonce' => 'def', 'url' => $authUrl]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('onelogin_auth', ['state' => 'abc', 'nonce' => 'def']);

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
                return ['state' => 'x', 'nonce' => 'y', 'url' => 'https://auth.example.com'];
            });

        $this->session->method('set');

        $this->handler->handle($this->buildRequest('https', 'localhost:7002'));

        $this->assertSame('https://localhost:7002/auth/redirect', $capturedRedirectUri);
    }

    public function testPreAuthRequestUrlIsNeverWritten(): void
    {
        $this->oneLoginService
            ->method('start')
            ->willReturn(['state' => 'x', 'nonce' => 'y', 'url' => 'https://auth.example.com']);

        $setCalls = [];
        $this->session
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$setCalls): void {
                $setCalls[] = $key;
            });

        $this->handler->handle($this->buildRequest());

        $this->assertNotContains('pre_auth_request_url', $setCalls);
    }

    public function testSessionReceivesExactStateAndNonceUnderOneloginAuthKey(): void
    {
        $state = 'state-abc-123';
        $nonce = 'nonce-xyz-456';

        $this->oneLoginService
            ->method('start')
            ->willReturn(['state' => $state, 'nonce' => $nonce, 'url' => 'https://auth.example.com']);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('onelogin_auth', $this->callback(function (array $data) use ($state, $nonce) {
                return $data['state'] === $state && $data['nonce'] === $nonce;
            }));

        $this->handler->handle($this->buildRequest());
    }
}
