<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LogoutHandler;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\OneLoginSignOut;
use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    private const string DONE_URL = 'https://www.gov.uk/done/lasting-power-of-attorney';
    private const string ID_TOKEN = 'header.payload.sig';
    private const string ONE_LOGIN_LOGOUT_URL = 'https://oidc.example.com/logout?id_token_hint=header.payload.sig';
    private const string MOCK_REDIRECT_URI = 'https://front.example.com/auth/redirect';

    private SessionInterface&MockObject $session;
    private OneLoginSignOut&MockObject $oneLoginSignOut;

    protected function setUp(): void
    {
        $this->session         = $this->createMock(SessionInterface::class);
        $this->oneLoginSignOut = $this->createMock(OneLoginSignOut::class);
    }

    private function createHandler(array $config = ['redirects' => ['logout' => self::DONE_URL]]): LogoutHandler
    {
        return new LogoutHandler(
            $config,
            new OneLoginSessionManager(),
            $this->oneLoginSignOut,
            new RedirectUriBuilder('https://front.example.com'),
        );
    }

    private function createRequest(?string $idToken = null, ?array $pendingLink = null): ServerRequest
    {
        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'onelogin_id_token'     => $idToken,
                'onelogin_pending_link' => $pendingLink,
                default                 => null,
            });

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testSessionIsClearedAndRegenerated(): void
    {
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $this->createHandler()->handle($this->createRequest());
    }

    public function testPasswordUserIsRedirectedToConfiguredLogoutUrl(): void
    {
        $this->oneLoginSignOut
            ->expects($this->once())
            ->method('url')
            ->with(null, '/goodbye', self::MOCK_REDIRECT_URI)
            ->willReturn(null);

        $response = $this->createHandler(['redirects' => ['logout' => '/goodbye']])->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/goodbye', $response->getHeaderLine('Location'));
    }

    public function testUserStillOnboardingIsSignedOutOfOneLoginWithThePendingLinksIdToken(): void
    {
        $this->oneLoginSignOut
            ->expects($this->once())
            ->method('url')
            ->with('pending.id.token', '/goodbye')
            ->willReturn('https://oidc.example.com/logout?id_token_hint=pending.id.token');

        $response = $this->createHandler(['redirects' => ['logout' => '/goodbye']])->handle($this->createRequest(
            null,
            ['sub' => 'urn:fdc:gov.uk:2022:newuser', 'email' => 'new@example.com', 'idToken' => 'pending.id.token'],
        ));

        $this->assertEquals('https://oidc.example.com/logout?id_token_hint=pending.id.token', $response->getHeaderLine('Location'));
    }

    public function testASignedInUsersIdTokenIsUsedRatherThanALeftoverPendingLink(): void
    {
        $this->oneLoginSignOut
            ->expects($this->once())
            ->method('url')
            ->with('session.id.token', '/goodbye')
            ->willReturn(null);

        $this->createHandler(['redirects' => ['logout' => '/goodbye']])->handle($this->createRequest(
            'session.id.token',
            ['sub' => 'urn:fdc:gov.uk:2022:newuser', 'email' => 'new@example.com', 'idToken' => 'pending.id.token'],
        ));
    }

    public function testRedirectsToRootAndSkipsOneLoginWhenNoConfiguredUrl(): void
    {
        $this->oneLoginSignOut->expects($this->never())->method('url');

        $response = $this->createHandler([])->handle($this->createRequest(self::ID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testHandlesNullSessionGracefully(): void
    {
        $this->oneLoginSignOut->method('url')->with(null, self::DONE_URL, self::MOCK_REDIRECT_URI)->willReturn(null);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, null);

        $response = $this->createHandler()->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }

    public function testOneLoginUserIsSignedOutLocallyThenSentToOneLogin(): void
    {
        $calls = [];

        $this->session->method('clear')->willReturnCallback(function () use (&$calls): void {
            $calls[] = 'clear';
        });

        $this->oneLoginSignOut
            ->expects($this->once())
            ->method('url')
            ->with(self::ID_TOKEN, self::DONE_URL, self::MOCK_REDIRECT_URI)
            ->willReturnCallback(function () use (&$calls): string {
                $calls[] = 'url';

                return self::ONE_LOGIN_LOGOUT_URL;
            });

        $response = $this->createHandler()->handle($this->createRequest(self::ID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(self::ONE_LOGIN_LOGOUT_URL, $response->getHeaderLine('Location'));
        $this->assertSame(['clear', 'url'], $calls);
    }

    public function testFallsBackToConfiguredLogoutUrlWhenOneLoginUrlIsUnavailable(): void
    {
        $this->oneLoginSignOut->method('url')->willReturn(null);

        $response = $this->createHandler()->handle($this->createRequest(self::ID_TOKEN));

        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }
}
