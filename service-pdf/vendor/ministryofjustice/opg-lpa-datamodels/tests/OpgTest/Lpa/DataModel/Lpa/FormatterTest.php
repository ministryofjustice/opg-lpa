<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use InvalidArgumentException;
use Opg\Lpa\DataModel\Lpa\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    public function testId()
    {
        $formatted = Formatter::id(12345678);
        $this->assertEquals('A000 1234 5678', $formatted);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The passed value must be an integer.
     */
    public function testIdString()
    {
        Formatter::id('27');
    }
}
