<?php

namespace MakeSharedTest\DataModel\Lpa;

use InvalidArgumentException;
use MakeShared\DataModel\Lpa\Formatter;
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The passed value must be an integer');
        Formatter::id('27');
    }

    public function testFlattenInstructionsOrPreferencesNull()
    {
        $this->assertEquals('', Formatter::flattenInstructionsOrPreferences(null));
    }

    public function testFlattenInstructionsOrPreferencesSingleLine()
    {
        $text = 'A single line of text';

        // expect length of text to be padded to 84 characters
        $expected = 'A single line of text                                                               ';

        $actual = Formatter::flattenInstructionsOrPreferences($text);

        $this->assertEquals($expected, $actual);
    }

    public function testFlattenInstructionsOrPreferencesMultipleLines()
    {
        $text = "My wealth is to be bequeathed unto my relatives over a " .
            "period not to exceed twenty five earth years.\r\n\r\n" .
            "In the case of my family not being able to take over my affairs, " .
            "the money and my other earthly belongings are to be distributed " .
            "equally throughout the land to all and sundry.";

        $expected = "My wealth is to be bequeathed unto my relatives over a period not to exceed twenty  \r\n" .
                    "five earth years.                                                                   \r\n" .
                    "In the case of my family not being able to take over my affairs, the money and my   \r\n" .
                    "other earthly belongings are to be distributed equally throughout the land to all   \r\n" .
                    "and sundry.                                                                         ";

        $actual = Formatter::flattenInstructionsOrPreferences($text);

        $this->assertEquals($expected, $actual);
    }

    public function testFlattenInstructionsOrPreferencesMultipleBlankLines()
    {
        $text = implode("\r\n", [
            'Line one.',
            '',
            'Line two.',
            '',
            'Line three.',
            '',
        ]);

        $expected = "Line one.                                                                           \r\n" .
                    "Line two.                                                                           \r\n" .
                    "Line three.                                                                         ";

        $actual = Formatter::flattenInstructionsOrPreferences($text);

        $this->assertEquals($expected, $actual);
    }
}
