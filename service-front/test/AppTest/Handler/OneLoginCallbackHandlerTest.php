<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginCallbackHandler;
use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OneLoginCallbackHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private OneLoginCallbackHandler $handler;

    private const array VALID_SESSION = [
        'state'        => 'valid-state-abc',
        'nonce'        => 'valid-nonce-xyz',
        'redirect_uri' => 'https://service.example.com/auth/redirect',
    ];

    private const array LINKED_RESULT = [
        'linked' => true,
        'sub'    => 'urn:fdc:gov.uk:2022:abc123',
        'email'  => 'user@example.com',
        'identity' => [
            'userId'         => 'user-id-1',
            'token'          => 'tok-abc',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
        ],
    ];

    private const array UNLINKED_RESULT = [
        'linked' => false,
        'sub'    => 'urn:fdc:gov.uk:2022:newuser',
        'email'  => 'newuser@example.com',
    ];

    protected function setUp(): void
    {
        $this->renderer        = $this->createMock(TemplateRendererInterface::class);
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->session         = $this->createMock(SessionInterface::class);
        $this->handler         = new OneLoginCallbackHandler(
            $this->renderer,
            $this->oneLoginService,
            $this->logger,
        );
    }

    private function buildRequest(
        string $queryString = 'code=authcode&state=valid-state-abc',
        ?array $sessionData = null,
    ): ServerRequest {
        $queryParams = [];
        parse_str($queryString, $queryParams);

        $this->session
            ->method('has')
            ->willReturnCallback(
                fn(string $key) => ($sessionData !== null && array_key_exists($key, $sessionData))
            );

        $this->session
            ->method('get')
            ->willReturnCallback(
                fn(string $key) => $sessionData[$key] ?? null
            );

        return (new ServerRequest())
            ->withUri(new Uri('https://service.example.com/auth/redirect?' . $queryString))
            ->withQueryParams($queryParams)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    // ─── Provider error path ───────────────────────────────────────────────

    public function testProviderErrorParameterLogsAndRendersError(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.provider_error', $this->arrayHasKey('error'));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/onelogin-error.twig', $this->arrayHasKey('error'))
            ->willReturn('<html>error</html>');

        $request = (new ServerRequest())
            ->withUri(new Uri('https://service.example.com/auth/redirect?error=access_denied&error_description=User+denied'))
            ->withQueryParams(['error' => 'access_denied', 'error_description' => 'User denied'])
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ─── Missing code / state ──────────────────────────────────────────────

    public function testMissingCodeReturnsErrorPage(): void
    {
        $this->renderer->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->buildRequest('state=valid-state-abc'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testMissingStateReturnsErrorPage(): void
    {
        $this->renderer->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->buildRequest('code=authcode'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ─── Missing session ──────────────────────────────────────────────────

    public function testMissingSessionDataLogsAndRendersError(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.session_missing');

        $this->renderer->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->buildRequest('code=c&state=s', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ─── State mismatch ───────────────────────────────────────────────────

    public function testStateMismatchLogsWarningAndRendersError(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.state_mismatch');

        $this->renderer->method('render')->willReturn('<html>error</html>');
        $this->session->method('unset');

        // State in URL differs from session
        $response = $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=WRONG-STATE',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testStateMismatchDoesNotCallApi(): void
    {
        $this->oneLoginService->expects($this->never())->method('callback');
        $this->renderer->method('render')->willReturn('<html>error</html>');
        $this->logger->method('warning');
        $this->session->method('unset');

        $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=WRONG-STATE',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );
    }

    // ─── Session always unset ─────────────────────────────────────────────

    public function testOneloginAuthSessionIsAlwaysUnsetOnError(): void
    {
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with('onelogin_auth');

        $this->renderer->method('render')->willReturn('<html>error</html>');
        $this->logger->method('warning');

        $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=WRONG-STATE',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );
    }

    public function testOneloginAuthSessionIsUnsetAfterCallbackException(): void
    {
        $this->oneLoginService->method('callback')->willThrowException(new RuntimeException('API down'));
        $this->logger->method('error');
        $this->renderer->method('render')->willReturn('<html>error</html>');

        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with('onelogin_auth');

        $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=valid-state-abc',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );
    }

    // ─── API exception ────────────────────────────────────────────────────

    public function testApiExceptionLogsAndRendersError(): void
    {
        $this->oneLoginService->method('callback')->willThrowException(new RuntimeException('bad response'));
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('auth.onelogin.callback_failed');

        $this->renderer->method('render')->willReturn('<html>error</html>');
        $this->session->method('regenerate');
        $this->session->method('unset');

        $response = $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=valid-state-abc',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ─── Linked happy path ────────────────────────────────────────────────

    public function testLinkedAccountRegeneratesAndSetsIdentityAndRedirectsToDashboard(): void
    {
        $this->oneLoginService->method('callback')->willReturn(self::LINKED_RESULT);
        $this->session->method('unset');

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->once())->method('clear');

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('identity', self::LINKED_RESULT['identity']);

        $response = $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=valid-state-abc',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testLinkedAccountHonoursPreAuthRequestUrl(): void
    {
        $this->oneLoginService->method('callback')->willReturn(self::LINKED_RESULT);
        $this->session->method('unset');
        $this->session->method('regenerate');
        $this->session->method('clear');
        $this->session->method('set');

        $preAuthUrl = '/user/lpa/123/complete';

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'onelogin_auth'        => self::VALID_SESSION,
                'pre_auth_request_url' => $preAuthUrl,
                default                => null,
            });

        $this->session
            ->method('has')
            ->willReturnCallback(fn(string $key) => in_array($key, ['onelogin_auth', 'pre_auth_request_url'], true));

        $request = (new ServerRequest())
            ->withUri(new Uri('https://service.example.com/auth/redirect?code=authcode&state=valid-state-abc'))
            ->withQueryParams(['code' => 'authcode', 'state' => 'valid-state-abc'])
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($preAuthUrl, $response->getHeaderLine('Location'));
    }

    public function testLinkedAccountPassesCorrectFieldsToCallback(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('callback')
            ->with(
                'authcode',
                'valid-state-abc',
                self::VALID_SESSION['nonce'],
                self::VALID_SESSION['redirect_uri'],
            )
            ->willReturn(self::LINKED_RESULT);

        $this->session->method('regenerate');
        $this->session->method('clear');
        $this->session->method('set');
        $this->session->method('unset');

        $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=valid-state-abc',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );
    }

    // ─── Unlinked path ────────────────────────────────────────────────────

    public function testUnlinkedAccountRegeneratesAndSetsPendingLinkAndRedirects(): void
    {
        $this->oneLoginService->method('callback')->willReturn(self::UNLINKED_RESULT);
        $this->session->method('unset');

        $this->session->expects($this->once())->method('regenerate');
        $this->session->expects($this->never())->method('clear');

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('onelogin_pending_link', [
                'sub'   => self::UNLINKED_RESULT['sub'],
                'email' => self::UNLINKED_RESULT['email'],
            ]);

        $response = $this->handler->handle(
            $this->buildRequest(
                'code=authcode&state=valid-state-abc',
                ['onelogin_auth' => self::VALID_SESSION],
            )
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/link-or-create-account', $response->getHeaderLine('Location'));
    }
}
