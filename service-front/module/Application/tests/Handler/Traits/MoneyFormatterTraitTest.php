<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Traits;

use Application\View\Helper\Traits\MoneyFormatterTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class MoneyFormatterTraitTest extends MockeryTestCase
{
    private $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new class {
            use MoneyFormatterTrait;
        };
    }

    public function testWholePoundsAsInt(): void
    {
        $this->assertSame('50', $this->formatter->formatMoney(50));
    }

    public function testPoundsAsString(): void
    {
        $this->assertSame('50', $this->formatter->formatMoney('50.00'));
    }

    public function testPoundsAndPenceAsFloat(): void
    {
        $this->assertSame('50.55', $this->formatter->formatMoney(50.55));
    }

    public function testPoundsAndPenceAsString(): void
    {
        $this->assertSame('50.55', $this->formatter->formatMoney('50.55'));
    }

    public function testLargeNumbers(): void
    {
        $this->assertSame('500,000,000.55', $this->formatter->formatMoney('500000000.55'));
        $this->assertSame('500,000,000.55', $this->formatter->formatMoney(500000000.55));
        $this->assertSame('500,000,000', $this->formatter->formatMoney('500000000'));
        $this->assertSame('500,000,000', $this->formatter->formatMoney(500000000));
    }

    public function testNonNumeric(): void
    {
        $this->assertSame('a', $this->formatter->formatMoney('a'));
    }
}
