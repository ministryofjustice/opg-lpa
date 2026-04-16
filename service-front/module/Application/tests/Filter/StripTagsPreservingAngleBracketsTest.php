<?php

declare(strict_types=1);

namespace ApplicationTest\Filter;

use Application\Filter\StripTagsPreservingAngleBrackets;
use PHPUnit\Framework\TestCase;

final class StripTagsPreservingAngleBracketsTest extends TestCase
{
    private StripTagsPreservingAngleBrackets $filter;

    protected function setUp(): void
    {
        $this->filter = new StripTagsPreservingAngleBrackets();
    }

    public function testStripsScriptTags(): void
    {
        $this->assertEquals(
            '',
            $this->filter->filter("<script>alert('xss')</script>")
        );
    }

    public function testStripsBoldTags(): void
    {
        $this->assertEquals(
            'bold text',
            $this->filter->filter('<b>bold</b> text')
        );
    }

    public function testStripsParagraphTags(): void
    {
        $this->assertEquals(
            'para',
            $this->filter->filter('<p>para</p>')
        );
    }

    public function testStripsNestedTags(): void
    {
        $this->assertEquals(
            'Hello world',
            $this->filter->filter('<div><p>Hello <strong>world</strong></p></div>')
        );
    }

    public function testStripsTagsWithDangerousAttributes(): void
    {
        $this->assertEquals(
            'a  b',
            $this->filter->filter('a <img src=x onerror=alert(1)> b')
        );
    }

    public function testPreservesLoneLeftAngleBracketWithSpace(): void
    {
        $this->assertEquals(
            'I would like < 5 visits per week',
            $this->filter->filter('I would like < 5 visits per week')
        );
    }

    public function testPreservesLoneRightAngleBracket(): void
    {
        $this->assertEquals(
            'Temperature > 30 degrees',
            $this->filter->filter('Temperature > 30 degrees')
        );
    }

    public function testPreservesBothAngleBrackets(): void
    {
        $this->assertEquals(
            'I want < 5 and > 3',
            $this->filter->filter('I want < 5 and > 3')
        );
    }

    public function testPreservesAmpersand(): void
    {
        $this->assertEquals(
            'Tom & Jerry < 5',
            $this->filter->filter('Tom & Jerry < 5')
        );
    }

    public function testNewlinesConvertedToCrLf(): void
    {
        $input = "Line one\nLine two\nLine three";
        $expected = "Line one\r\nLine two\r\nLine three";
        $this->assertEquals($expected, $this->filter->filter($input));
    }

    public function testCrLfPreserved(): void
    {
        $input = "Line one\r\nLine two\r\nLine three";
        $this->assertEquals($input, $this->filter->filter($input));
    }

    public function testPreservesTextAfterLoneAngleBracket(): void
    {
        $input = "Line one has a < sign\nLine two should still be here";
        $expected = "Line one has a < sign\r\nLine two should still be here";
        $this->assertEquals($expected, $this->filter->filter($input));
    }

    public function testReturnsNonStringInputUnchanged(): void
    {
        $this->assertEquals(42, $this->filter->filter(42));
        $this->assertNull($this->filter->filter(null));
        $this->assertTrue($this->filter->filter(true));
    }

    public function testEmptyString(): void
    {
        $this->assertEquals('', $this->filter->filter(''));
    }

    public function testPlainTextPassesThrough(): void
    {
        $this->assertEquals(
            'Just some plain text with no special chars.',
            $this->filter->filter('Just some plain text with no special chars.')
        );
    }
}
