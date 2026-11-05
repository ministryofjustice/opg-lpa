<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\SignInPage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SignInPageTest extends TestCase
{
    private string|false $oneLoginEnabled;

    protected function setUp(): void
    {
        $this->oneLoginEnabled = getenv('ONELOGIN_ENABLED');
    }

    protected function tearDown(): void
    {
        putenv($this->oneLoginEnabled === false ? 'ONELOGIN_ENABLED' : 'ONELOGIN_ENABLED=' . $this->oneLoginEnabled);
    }

    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function pathProvider(): array
    {
        return [
            'One Login on'                => ['true', null, '/home'],
            'One Login on, timed out'     => ['true', 'timeout', '/home/timeout'],
            'One Login off'               => ['false', null, '/login'],
            'One Login off, system error' => ['false', 'internal-system-error', '/login/internal-system-error'],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testPath(string $oneLoginEnabled, ?string $state, string $expected): void
    {
        putenv('ONELOGIN_ENABLED=' . $oneLoginEnabled);

        $this->assertSame($expected, SignInPage::path($state));
    }
}
