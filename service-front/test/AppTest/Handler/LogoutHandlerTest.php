<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LogoutHandler;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class LogoutHandlerTest extends TestCase
{
    private const string DONE_URL = 'https://www.gov.uk/done/lasting-power-of-attorney';
    private const string ID_TOKEN = 'header.payload.sig';
    private const string ONE_LOGIN_LOGOUT_URL = 'https://oidc.example.com/logout?id_token_hint=header.payload.sig';

    private SessionInterface&MockObject $session;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->session         = $this->createMock(SessionInterface::class);
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
    }

    private function createHandler(array $config = ['redirects' => ['logout' => self::DONE_URL]], bool $oneLoginEnabled = true): LogoutHandler
    {
        return new LogoutHandler(
            $config,
            $oneLoginEnabled,
            $this->oneLoginService,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    private function createRequest(?string $idToken = null): ServerRequest
    {
        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => $key === 'onelogin_id_token' ? $idToken : null);

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

    public function testRedirectsToConfiguredLogoutUrl(): void
    {
        $response = $this->createHandler(['redirects' => ['logout' => '/goodbye']])->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/goodbye', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToRootWhenNoConfiguredUrl(): void
    {
        $response = $this->createHandler([])->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testHandlesNullSessionGracefully(): void
    {
        $this->oneLoginService->expects($this->never())->method('logoutUrl');

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, null);

        $response = $this->createHandler()->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }

    public function testPasswordUserIsNotSentToOneLogin(): void
    {
        $this->oneLoginService->expects($this->never())->method('logoutUrl');

        $response = $this->createHandler()->handle($this->createRequest());

        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }

    public function testOneLoginUserIsNotSentToOneLoginWhenFeatureIsOff(): void
    {
        $this->oneLoginService->expects($this->never())->method('logoutUrl');

        $response = $this->createHandler(oneLoginEnabled: false)->handle($this->createRequest(self::ID_TOKEN));

        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }

    public function testOneLoginUserIsSignedOutLocallyThenSentToOneLogin(): void
    {
        $calls = [];

        $this->session->method('clear')->willReturnCallback(function () use (&$calls): void {
            $calls[] = 'clear';
        });

        $this->oneLoginService
            ->expects($this->once())
            ->method('logoutUrl')
            ->with(self::ID_TOKEN, self::DONE_URL)
            ->willReturnCallback(function () use (&$calls): string {
                $calls[] = 'logoutUrl';

                return self::ONE_LOGIN_LOGOUT_URL;
            });

        $response = $this->createHandler()->handle($this->createRequest(self::ID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(self::ONE_LOGIN_LOGOUT_URL, $response->getHeaderLine('Location'));
        $this->assertSame(['clear', 'logoutUrl'], $calls);
    }

    /**
     * @return array<string, array{Throwable}>
     */
    public static function oneLoginFailureProvider(): array
    {
        return [
            'API error'         => [new RuntimeException('API unavailable')],
            'transport failure' => [new class ('API unavailable') extends Exception implements ClientExceptionInterface {
            }],
        ];
    }

    #[DataProvider('oneLoginFailureProvider')]
    public function testFallsBackToConfiguredLogoutUrlWhenOneLoginUrlCannotBeBuilt(Throwable $failure): void
    {
        $this->session->expects($this->once())->method('clear');

        $this->oneLoginService
            ->method('logoutUrl')
            ->willThrowException($failure);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.logout_url_failed', ['message' => 'API unavailable']);

        $response = $this->createHandler()->handle($this->createRequest(self::ID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(self::DONE_URL, $response->getHeaderLine('Location'));
    }
}
