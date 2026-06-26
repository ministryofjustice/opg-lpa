<?php

declare(strict_types=1);

namespace AppTest\View\DateFormatter;

use App\View\DateFormatter\DateFormatterPlatesExtension;
use DateTime;
use DateTimeZone;
use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

final class DateFormatterPlatesExtensionTest extends TestCase
{
    private DateFormatterPlatesExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new DateFormatterPlatesExtension();
    }

    public function testRegisterFunctionWithEngine(): void
    {
        $engine = $this->createMock(Engine::class);
        $engine->expects(self::once())
            ->method('registerFunction')
            ->with('dateFormat', [$this->extension, 'dateFormat']);

        $this->extension->register($engine);
    }

    // --- Timezone conversion: always Europe/London ---

    public function testDateFormatConvertsSummerUTCToLondon(): void
    {
        // Summer: UTC+1 (BST). 14:30 UTC → 15:30 BST
        $date = new DateTime('2026-06-15 14:30:00', new DateTimeZone('UTC'));

        $result = $this->extension->dateFormat($date);

        self::assertSame('15th Jun 2026 at 3:30:00 pm', $result);
    }

    public function testDateFormatConvertsWinterUTCToLondon(): void
    {
        // Winter: UTC+0 (GMT). 14:30 UTC → 14:30 GMT (no offset change)
        $date = new DateTime('2026-01-15 14:30:00', new DateTimeZone('UTC'));

        $result = $this->extension->dateFormat($date);

        self::assertSame('15th Jan 2026 at 2:30:00 pm', $result);
    }

    public function testDateFormatConvertsSummerNearMidnightRollsDateForward(): void
    {
        // 23:45 UTC in summer → 00:45 BST next day
        $date = new DateTime('2026-07-04 23:45:00', new DateTimeZone('UTC'));

        $result = $this->extension->dateFormat($date);

        self::assertSame('5th Jul 2026 at 12:45:00 am', $result);
    }

    public function testDateFormatConvertsFromNonUTCTimezone(): void
    {
        // America/New_York (EDT = UTC-4) → UTC → Europe/London (BST = UTC+1)
        // 10:30 EDT = 14:30 UTC = 15:30 BST
        $date = new DateTime('2026-06-15 10:30:00', new DateTimeZone('America/New_York'));

        $result = $this->extension->dateFormat($date);

        self::assertSame('15th Jun 2026 at 3:30:00 pm', $result);
    }

    public function testDateFormatDoesNotModifyOriginalDateTime(): void
    {
        $date = new DateTime('2026-06-15 14:30:00', new DateTimeZone('UTC'));

        $this->extension->dateFormat($date);

        self::assertSame('UTC', $date->getTimezone()->getName());
        self::assertSame('2026-06-15 14:30:00', $date->format('Y-m-d H:i:s'));
    }

    // --- String input ---

    public function testDateFormatConvertsStringWithUTCOffsetToLondon(): void
    {
        $result = $this->extension->dateFormat('2026-06-15T14:30:00+00:00');

        self::assertSame('15th Jun 2026 at 3:30:00 pm', $result);
    }

    public function testDateFormatConvertsStringWithNoTimezoneUsingLondon(): void
    {
        // No tz in string: PHP parses using the server default, then converts to Europe/London
        $result = $this->extension->dateFormat('2026-03-05 09:05:03');

        self::assertMatchesRegularExpression(
            '/^\d{1,2}(st|nd|rd|th) [A-Z][a-z]{2} \d{4} at \d{1,2}:\d{2}:\d{2} (am|pm)$/',
            $result
        );
        self::assertStringContainsString('5th Mar 2026', $result);
    }

    // --- Output format ---

    public function testDateFormatOutputFormat(): void
    {
        $date = new DateTime('2026-12-25 09:05:03', new DateTimeZone('Europe/London'));

        $result = $this->extension->dateFormat($date);

        self::assertMatchesRegularExpression(
            '/^\d{1,2}(st|nd|rd|th) [A-Z][a-z]{2} \d{4} at \d{1,2}:\d{2}:\d{2} (am|pm)$/',
            $result
        );
        self::assertSame('25th Dec 2026 at 9:05:03 am', $result);
    }

    public function testDateFormatWithMidnightTime(): void
    {
        $date = new DateTime('2026-12-25 00:00:00', new DateTimeZone('Europe/London'));

        self::assertSame('25th Dec 2026 at 12:00:00 am', $this->extension->dateFormat($date));
    }

    public function testDateFormatWithNoonTime(): void
    {
        $date = new DateTime('2026-12-25 12:00:00', new DateTimeZone('Europe/London'));

        self::assertSame('25th Dec 2026 at 12:00:00 pm', $this->extension->dateFormat($date));
    }

    // --- Invalid / edge-case inputs ---

    public function invalidValueProvider(): array
    {
        return [
            'invalid string' => ['not a date', null, 'not a date'],
            'null value'     => [null, null, null],
            'integer'        => [123, null, 123],
            'array'          => [[], null, []],
        ];
    }

    /** @dataProvider invalidValueProvider */
    public function testDateFormatWithInvalidValue(mixed $value, mixed $default, mixed $expected): void
    {
        self::assertSame($expected, $this->extension->dateFormat($value, $default));
    }

    public function testDateFormatWithEmptyString(): void
    {
        // DateTime('') parses as "now"; should return a formatted date, not the default
        $result = $this->extension->dateFormat('', 'default');

        self::assertNotSame('default', $result);
        self::assertIsString($result);
        self::assertStringContainsString('at', $result);
    }

    public function testDateFormatWithNonDateTimeObject(): void
    {
        $obj = new \stdClass();

        self::assertSame($obj, $this->extension->dateFormat($obj, null));
    }

    public function testDateFormatWithCustomDefault(): void
    {
        self::assertSame('Custom Default', $this->extension->dateFormat('not a date', 'Custom Default'));
    }

    public function testDateFormatWithNullAndCustomDefault(): void
    {
        self::assertSame('N/A', $this->extension->dateFormat(null, 'N/A'));
    }
}
