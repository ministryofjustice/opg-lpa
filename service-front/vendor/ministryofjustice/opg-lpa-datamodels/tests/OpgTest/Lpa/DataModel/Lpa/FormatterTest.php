<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Formatter;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testId()
    {
        $formatted = Formatter::id(12345678);
        $this->assertEquals('A000 1234 5678', $formatted);
    }

    public function testIdString()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'The passed value must be an integer.');

        Formatter::id('27');
    }
}
