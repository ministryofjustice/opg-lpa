<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSignOut;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class OneLoginSignOutTest extends TestCase
{
    private const string ID_TOKEN = 'header.payload.sig';
    private const string DONE_URL = 'https://www.gov.uk/done/lasting-power-of-attorney';
    private const string MOCK_REDIRECT_URI = 'https://front-ssl/auth/redirect';
    private const string ONE_LOGIN_LOGOUT_URL = 'https://oidc.example.com/logout?id_token_hint=header.payload.sig';

    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
    }

    private function signOut(bool $oneLoginEnabled = true): OneLoginSignOut
    {
        return new OneLoginSignOut($oneLoginEnabled, $this->oneLoginService, $this->logger);
    }

    public function testReturnsOneLoginLogoutUrl(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('logoutUrl')
            ->with(self::ID_TOKEN, self::DONE_URL, self::MOCK_REDIRECT_URI)
            ->willReturn(self::ONE_LOGIN_LOGOUT_URL);

        $this->assertSame(
            self::ONE_LOGIN_LOGOUT_URL,
            $this->signOut()->url(self::ID_TOKEN, self::DONE_URL, self::MOCK_REDIRECT_URI),
        );
    }

    public function testReturnsNullWithoutCallingTheApiWhenOneLoginIsOff(): void
    {
        $this->oneLoginService->expects($this->never())->method('logoutUrl');

        $this->assertNull($this->signOut(oneLoginEnabled: false)->url(self::ID_TOKEN, self::DONE_URL));
    }

    public function testReturnsNullWithoutCallingTheApiWhenThereIsNoIdToken(): void
    {
        $this->oneLoginService->expects($this->never())->method('logoutUrl');

        $this->assertNull($this->signOut()->url(null, self::DONE_URL));
    }

    /**
     * @return array<string, array{Throwable}>
     */
    public static function failureProvider(): array
    {
        return [
            'API error'         => [new RuntimeException('API unavailable')],
            'transport failure' => [new class ('API unavailable') extends Exception implements ClientExceptionInterface {
            }],
        ];
    }

    #[DataProvider('failureProvider')]
    public function testReturnsNullAndLogsWhenTheUrlCannotBeBuilt(Throwable $failure): void
    {
        $this->oneLoginService->method('logoutUrl')->willThrowException($failure);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.logout_url_failed', ['message' => 'API unavailable']);

        $this->assertNull($this->signOut()->url(self::ID_TOKEN, self::DONE_URL));
    }
}
