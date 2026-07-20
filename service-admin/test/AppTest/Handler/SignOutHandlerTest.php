<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SignOutHandler;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SignOutHandlerTest extends TestCase
{
    private LoggerInterface|MockObject $mockLogger;
    private SessionInterface|MockObject $mockSession;

    protected function setUp(): void
    {
        $this->mockLogger  = $this->createMock(LoggerInterface::class);
        $this->mockSession = $this->createMock(SessionInterface::class);
    }

    private function makeRequest(): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute(SessionInterface::class, $this->mockSession);
    }

    public function testClearsSessionAndRedirectsLocallyWhenNoCognitoLogoutUrl(): void
    {
        $handler = new SignOutHandler(null, null);
        $handler->setLogger($this->mockLogger);
        $handler->setUrlHelper($this->createMock(UrlHelper::class));

        $this->mockSession->expects($this->once())->method('clear');
        $this->mockSession->expects($this->once())->method('set')->with('signed_out', true);

        $response = $handler->handle($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testRedirectsToCognitoLogoutUrlWithoutClearingAlbCookieWhenNameNotConfigured(): void
    {
        $handler = new SignOutHandler('https://cognito.example.com/logout', null);
        $handler->setLogger($this->mockLogger);
        $handler->setUrlHelper($this->createMock(UrlHelper::class));

        $response = $handler->handle($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://cognito.example.com/logout', $response->getHeaderLine('Location'));
        $this->assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testRedirectsToCognitoLogoutUrlAndExpiresAlbSessionCookieShards(): void
    {
        $handler = new SignOutHandler('https://cognito.example.com/logout', 'AWSELBAuthSessionCookie');
        $handler->setLogger($this->mockLogger);
        $handler->setUrlHelper($this->createMock(UrlHelper::class));

        $response = $handler->handle($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://cognito.example.com/logout', $response->getHeaderLine('Location'));

        $setCookieHeaders = $response->getHeader('Set-Cookie');
        $this->assertCount(10, $setCookieHeaders);
        $this->assertStringContainsString('AWSELBAuthSessionCookie-0=; Path=/; Expires=', $setCookieHeaders[0]);
        $this->assertStringContainsString('; Secure; HttpOnly', $setCookieHeaders[0]);
        $this->assertStringContainsString('AWSELBAuthSessionCookie-9=', $setCookieHeaders[9]);

        // Assert the expiry date is in the past, without pinning to the epoch or
        // any other specific date, since the exact "expired" value is an implementation detail.
        preg_match('/Expires=([^;]+);/', $setCookieHeaders[0], $matches);
        $this->assertLessThan(new \DateTimeImmutable(), new \DateTimeImmutable($matches[1]));
    }
}
