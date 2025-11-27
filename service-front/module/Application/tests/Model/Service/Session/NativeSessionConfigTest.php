<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\NativeSessionConfig;
use PHPUnit\Framework\TestCase;

final class NativeSessionConfigTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Backup ini settings to restore later
        ini_set('session.cookie_secure', ini_get('session.cookie_secure'));
        ini_set('session.cookie_httponly', ini_get('session.cookie_httponly'));
        ini_set('session.cookie_samesite', ini_get('session.cookie_samesite'));
        ini_set('session.gc_probability', ini_get('session.gc_probability'));
        ini_set('session.gc_divisor', ini_get('session.gc_divisor'));
    }

    public function testConfigureSetsIniAndRegistersHandler(): void
    {
        $handler = new FakeSaveHandler();
        $cfg = [
            'name'            => 'lpa2-test',
            'cookie_secure'   => true,
            'cookie_httponly' => true,
            'gc_probability'  => 0,
        ];

        $sut = new NativeSessionConfig($cfg, $handler);
        $sut->configure();

        $this->assertSame('lpa2-test', session_name());
        $this->assertSame('1', ini_get('session.cookie_secure'));
        $this->assertSame('1', ini_get('session.cookie_httponly'));
        $this->assertContains(ini_get('session.cookie_samesite'), ['Lax', 'Strict', 'None']);
        $this->assertSame('0', ini_get('session.gc_probability'));
        $this->assertSame('100', ini_get('session.gc_divisor'));

        $sut->startIfNeeded();
        $this->assertTrue($handler->opened);

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartIfNeededIsIdempotent(): void
    {
        $handler = new FakeSaveHandler();
        $sut = new NativeSessionConfig(['name' => 'lpa2-test'], $handler);
        $sut->configure();

        $sut->startIfNeeded();
        $firstId = session_id();

        $sut->startIfNeeded();
        $this->assertSame($firstId, session_id());
    }
}
