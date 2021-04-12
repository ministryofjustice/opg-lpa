<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\MoneyFormat;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class MoneyFormatTest extends MockeryTestCase
{
    public function testInvokeWithWholePounds():void
    {
        $amount = 50;
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals($amount, $result);
    }

    public function testInvokeWithPoundsAndPence():void
    {
        $amount = 50.55;
        $moneyFormat = new MoneyFormat();
        $result = $moneyFormat($amount);

        $this->assertEquals($amount, $result);
    }
}
