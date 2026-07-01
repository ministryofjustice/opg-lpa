<?php

declare(strict_types=1);

namespace AppTest\Filter;

use App\Filter\StripTagsPreservingAngleBrackets;
use PHPUnit\Framework\TestCase;

final class StripTagsPreservingAngleBracketsTest extends TestCase
{
    private StripTagsPreservingAngleBrackets $filter;

    protected function setUp(): void
    {
        $this->filter = new StripTagsPreservingAngleBrackets();
    }

    public function testNullIsReturnedUnchanged(): void
    {
        $this->assertNull($this->filter->filter(null));
    }

    public function testIntegerIsReturnedUnchanged(): void
    {
        $this->assertSame(42, $this->filter->filter(42));
    }

    public function testArrayIsReturnedUnchanged(): void
    {
        $input = ['a' => 'b'];
        $this->assertSame($input, $this->filter->filter($input));
    }

    public function testBooleanIsReturnedUnchanged(): void
    {
        $this->assertTrue($this->filter->filter(true));
        $this->assertFalse($this->filter->filter(false));
    }

    public function testEmptyStringIsReturnedAsEmptyString(): void
    {
        $this->assertSame('', $this->filter->filter(''));
    }

    public function testPlainTextIsPreserved(): void
    {
        $this->assertSame('Hello world', $this->filter->filter('Hello world'));
    }

    public function testHtmlTagsAreStripped(): void
    {
        $this->assertSame('Hello world', $this->filter->filter('<b>Hello</b> world'));
    }

    public function testParagraphTagsAreStripped(): void
    {
        $this->assertSame('Some text', $this->filter->filter('<p>Some text</p>'));
    }

    public function testScriptTagsAreStripped(): void
    {
        $result = $this->filter->filter('<script>alert("xss")</script>text');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert(', $result);
        $this->assertStringContainsString('text', $result);
    }

    public function testXssOnEventHandlerIsRemoved(): void
    {
        $result = $this->filter->filter('<img src="x" onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function testLoneOpenAngleBracketIsPreserved(): void
    {
        $result = $this->filter->filter('5 < 10');
        $this->assertSame('5 < 10', $result);
    }

    public function testLoneCloseAngleBracketIsPreserved(): void
    {
        $result = $this->filter->filter('10 > 5');
        $this->assertSame('10 > 5', $result);
    }

    public function testBothLoneAngleBracketsArePreserved(): void
    {
        $result = $this->filter->filter('a < b > c');
        $this->assertSame('a < b > c', $result);
    }

    public function testNestedTagsAreFullyStripped(): void
    {
        $result = $this->filter->filter('<div><span><a href="#">link</a></span></div>');
        $this->assertSame('link', $result);
    }

    public function testLineFeedIsNormalisedToCrLf(): void
    {
        $result = $this->filter->filter("line1\nline2");
        $this->assertSame("line1\r\nline2", $result);
    }

    public function testExistingCrLfIsNotDoubled(): void
    {
        $result = $this->filter->filter("line1\r\nline2");
        $this->assertSame("line1\r\nline2", $result);
    }

    public function testMultilineInputNormalisedCorrectly(): void
    {
        $result = $this->filter->filter("first\nsecond\nthird");
        $this->assertSame("first\r\nsecond\r\nthird", $result);
    }

    public function testHtmlSpecialCharsInTextArePreservedAfterDecoding(): void
    {
        // Ampersand should survive (it won't be decoded to &amp; since it's plain text)
        $result = $this->filter->filter('Tom & Jerry');
        $this->assertStringContainsString('Tom', $result);
        $this->assertStringContainsString('Jerry', $result);
    }

    public function testMixedHtmlAndTextContent(): void
    {
        $result = $this->filter->filter('Before <em>middle</em> after');
        $this->assertSame('Before middle after', $result);
    }

    public function testCommentTagsAreStripped(): void
    {
        $result = $this->filter->filter('text<!-- comment -->more');
        $this->assertStringNotContainsString('<!--', $result);
        $this->assertStringContainsString('text', $result);
        $this->assertStringContainsString('more', $result);
    }
}
