<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\SafeRedirectPath;
use Laminas\Diactoros\Response\RedirectResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SafeRedirectPathTest extends TestCase
{
    #[DataProvider('acceptedDataProvider')]
    public function testAcceptsSameSiteTargets(string $candidate, string $expected): void
    {
        $this->assertSame($expected, SafeRedirectPath::filter($candidate));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedDataProvider(): array
    {
        return [
            'dashboard'              => ['/user/dashboard', '/user/dashboard'],
            'shared space dashboard' => ['/shared-space/dashboard', '/shared-space/dashboard'],
            'deep link'              => ['/lpa/123/checkout', '/lpa/123/checkout'],
            'root'                   => ['/', '/'],
            'trailing slash'         => ['/lpa/123/', '/lpa/123/'],
            'colon in path'          => ['/guide/section:one', '/guide/section:one'],
            'at sign in path'        => ['/user/name@example.com', '/user/name@example.com'],

            'query preserved'        => ['/lpa/123/reuse-details?include-trusts=1', '/lpa/123/reuse-details?include-trusts=1'],
            'multiple query params'  => ['/lpa/1?a=1&b=2', '/lpa/1?a=1&b=2'],

            'backslash encoded'      => ['/\\evil.example', '/%5Cevil.example'],
            'backslash mid path'     => ['/user\\dashboard', '/user%5Cdashboard'],
            'crlf encoded'           => ["/user/dashboard\r\nX-Injected: 1", '/user/dashboard__X-Injected:%201'],
            'tab encoded'            => ["/user/\tdashboard", '/user/_dashboard'],
            'fragment dropped'       => ['/user/dashboard#section', '/user/dashboard'],
        ];
    }

    #[DataProvider('rejectedDataProvider')]
    public function testRejectsAnythingThatLeavesThisOrigin(mixed $candidate): void
    {
        $this->assertNull(SafeRedirectPath::filter($candidate));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function rejectedDataProvider(): array
    {
        return [
            'null'                     => [null],
            'empty string'             => [''],
            'integer'                  => [123],
            'array'                    => [['/user/dashboard']],
            'object'                   => [new \stdClass()],
            'true'                     => [true],

            'protocol relative'        => ['//evil.example/'],
            'protocol relative triple' => ['///evil.example/'],
            'protocol relative port'   => ['//evil.example:80/x'],
            'protocol relative at'     => ['//evil.example/@ignored'],
            'protocol relative dots'   => ['//evil.example/%2e%2e'],

            // Carries a scheme.
            'absolute https'           => ['https://evil.example/x'],
            'absolute http'            => ['http://evil.example/x'],
            'javascript scheme'        => ['javascript:alert(1)'],
            'data scheme'              => ['data:text/html,<script>'],

            'relative path'            => ['user/dashboard'],
            'bare word'                => ['dashboard'],
        ];
    }

    #[DataProvider('everyCandidateDataProvider')]
    public function testResultCanNeverPointAtAnotherOrigin(mixed $candidate): void
    {
        $result = SafeRedirectPath::filter($candidate);

        if ($result === null) {
            $this->assertNull($result);

            return;
        }

        $location = (new RedirectResponse($result))->getHeaderLine('Location');

        $this->assertStringStartsWith('/', $location);
        $this->assertStringStartsNotWith('//', $location);
        $this->assertDoesNotMatchRegularExpression('#^/[\\\\]#', $location);
        $this->assertDoesNotMatchRegularExpression('#^[a-z][a-z0-9+.-]*:#i', $location);
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function everyCandidateDataProvider(): iterable
    {
        foreach (self::acceptedDataProvider() as $name => [$candidate]) {
            yield 'accepted: ' . $name => [$candidate];
        }

        foreach (self::rejectedDataProvider() as $name => [$candidate]) {
            yield 'rejected: ' . $name => [$candidate];
        }
    }
}
