<?php

declare(strict_types=1);

namespace ApplicationTest\View\Helper;

use Application\View\Helper\MoneyFormat;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class MoneyFormatTest extends MockeryTestCase
{
    public function testInvokeWithWholePoundsAsInt(): void
    {
        $amount = 50;
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals('50', $result);
    }

    public function testInvokeWithPoundsAsString(): void
    {
        $amount = '50.00';
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals('50', $result);
    }

    public function testInvokeWithPoundsAndPenceAsFloat(): void
    {
        $amount = 50.55;
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals('50.55', $result);
    }

    public function testInvokeWithPoundsAndPenceAsString(): void
    {
        $amount = '50.55';
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals('50.55', $result);
    }

    public function testInvokeWithLoadsOfPounds(): void
    {
        $moneyFormat = new MoneyFormat();

        $amount = '500000000.55';
        $this->assertEquals('500,000,000.55', $moneyFormat($amount));

        $amount = 500000000.55;
        $this->assertEquals('500,000,000.55', $moneyFormat($amount));

        $amount = '500000000';
        $this->assertEquals('500,000,000', $moneyFormat($amount));

        $amount = 500000000;
        $this->assertEquals('500,000,000', $moneyFormat($amount));
    }

    public function testInvokeWithNonNumeric(): void
    {
        $moneyFormat = new MoneyFormat();

        $amount = 'a';
        $this->assertEquals('a', $moneyFormat($amount));
    }
}
