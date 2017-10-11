<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    public function testId()
    {
        $formatted = Formatter::id(12345678);
        $this->assertEquals('A000 1234 5678', $formatted);
    }

    public function testIdString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The passed value must be an integer.');

        Formatter::id('27');
    }
}
