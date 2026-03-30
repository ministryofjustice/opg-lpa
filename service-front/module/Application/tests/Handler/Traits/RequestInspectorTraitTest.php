<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Traits;

use Application\Handler\Traits\RequestInspectorTrait;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestInspectorTraitTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        $this->subject = new class {
            use RequestInspectorTrait;
        };
    }

    public static function xmlHttpRequestProvider(): array
    {
        return [
            'exact header value'     => ['XMLHttpRequest', true],
            'mixed case header value' => ['XmLhTtPrEqUeSt', true],
            'wrong header value'     => ['fetch', false],
            'empty header value'     => ['', false],
        ];
    }

    #[DataProvider('xmlHttpRequestProvider')]
    public function testIsXmlHttpRequestWithHeader(string $headerValue, bool $expected): void
    {
        $request = (new ServerRequest())->withHeader('X-Requested-With', $headerValue);

        $this->assertSame($expected, $this->subject->isXmlHttpRequest($request));
    }

    public function testReturnsFalseWhenHeaderIsAbsent(): void
    {
        $this->assertFalse($this->subject->isXmlHttpRequest(new ServerRequest()));
    }
}
