<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\RegisterSessionSaveHandlerMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class RegisterSessionSaveHandlerMiddlewareTest extends TestCase
{
    private string $originalSessionName;
    private string $originalCookieSecure;
    private string $originalCookieHttponly;
    private string $originalCookieSamesite;
    private string $originalGcProbability;
    private string $originalGcDivisor;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->originalSessionName    = session_name();
        $this->originalCookieSecure   = (string) ini_get('session.cookie_secure');
        $this->originalCookieHttponly = (string) ini_get('session.cookie_httponly');
        $this->originalCookieSamesite = (string) ini_get('session.cookie_samesite');
        $this->originalGcProbability  = (string) ini_get('session.gc_probability');
        $this->originalGcDivisor      = (string) ini_get('session.gc_divisor');
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_name($this->originalSessionName);
        ini_set('session.cookie_secure', $this->originalCookieSecure);
        ini_set('session.cookie_httponly', $this->originalCookieHttponly);
        ini_set('session.cookie_samesite', $this->originalCookieSamesite);
        ini_set('session.gc_probability', $this->originalGcProbability);
        ini_set('session.gc_divisor', $this->originalGcDivisor);
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());
        return $handler;
    }

    public function testAppliesAllSessionSettings(): void
    {
        $saveHandler = $this->createMock(\SessionHandlerInterface::class);
        $middleware = new RegisterSessionSaveHandlerMiddleware($saveHandler, [
            'name'            => 'lpa2-test',
            'cookie_secure'   => true,
            'cookie_httponly' => true,
            'gc_probability'  => 0,
        ]);

        $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertSame('lpa2-test', session_name());
        $this->assertSame('1', ini_get('session.cookie_secure'));
        $this->assertSame('1', ini_get('session.cookie_httponly'));
        $this->assertSame('0', ini_get('session.gc_probability'));
        $this->assertSame('100', ini_get('session.gc_divisor'));
    }

    public function testSetsSamesiteLaxWhenUnset(): void
    {
        ini_set('session.cookie_samesite', '');

        $saveHandler = $this->createMock(\SessionHandlerInterface::class);
        $middleware = new RegisterSessionSaveHandlerMiddleware($saveHandler, []);

        $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertSame('Lax', ini_get('session.cookie_samesite'));
    }

    public function testDoesNotOverrideExistingSamesiteSetting(): void
    {
        ini_set('session.cookie_samesite', 'Strict');

        $saveHandler = $this->createMock(\SessionHandlerInterface::class);
        $middleware = new RegisterSessionSaveHandlerMiddleware($saveHandler, []);

        $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertSame('Strict', ini_get('session.cookie_samesite'));
    }

    public function testOmittedSettingsAreNotApplied(): void
    {
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_httponly', '0');

        $saveHandler = $this->createMock(\SessionHandlerInterface::class);
        $middleware = new RegisterSessionSaveHandlerMiddleware($saveHandler, []);

        $middleware->process(new ServerRequest(), $this->makeHandler());

        $this->assertSame('0', ini_get('session.cookie_secure'));
        $this->assertSame('0', ini_get('session.cookie_httponly'));
    }

    public function testDelegatesRequestToInnerHandler(): void
    {
        $saveHandler = $this->createMock(\SessionHandlerInterface::class);
        $middleware = new RegisterSessionSaveHandlerMiddleware($saveHandler, []);

        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn(new EmptyResponse());

        $middleware->process($request, $handler);
    }
}
