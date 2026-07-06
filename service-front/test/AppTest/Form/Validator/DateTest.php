<?php

declare(strict_types=1);

namespace AppTest\Form\Validator;

use App\Form\Validator\Date;
use Exception;
use Laminas\Validator\Date as LaminasDateValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTest extends TestCase
{
    public function testValidDateArrayReturnsTrue(): void
    {
        $validator = new Date();

        $this->assertTrue($validator->isValid([
            'day' => '29',
            'month' => '2',
            'year' => '2024',
        ]));
        $this->assertSame([], $validator->getMessages());
    }

    #[DataProvider('emptyDateProvider')]
    public function testMissingDatePartReturnsEmptyDateError(array $value): void
    {
        $validator = new Date();

        $this->assertFalse($validator->isValid($value));
        $this->assertArrayHasKey(Date::EMPTY_DATE, $validator->getMessages());
    }

    public static function emptyDateProvider(): array
    {
        return [
            'missing day value' => [[
                'day' => '',
                'month' => '2',
                'year' => '2024',
            ]],
            'missing month value' => [[
                'day' => '29',
                'month' => '',
                'year' => '2024',
            ]],
            'missing year value' => [[
                'day' => '29',
                'month' => '2',
                'year' => '',
            ]],
        ];
    }

    public function testNonNumericDateReturnsInvalidDateError(): void
    {
        $validator = new Date();

        $this->assertFalse($validator->isValid([
            'day' => 'aa',
            'month' => '2',
            'year' => '2024',
        ]));
        $this->assertArrayHasKey(LaminasDateValidator::INVALID_DATE, $validator->getMessages());
    }

    public function testInvalidCalendarDateReturnsInvalidDateError(): void
    {
        $validator = new Date();

        $this->assertFalse($validator->isValid([
            'day' => '31',
            'month' => '2',
            'year' => '2024',
        ]));
        $this->assertArrayHasKey(LaminasDateValidator::INVALID_DATE, $validator->getMessages());
    }

    public function testMissingKeysThrowsException(): void
    {
        $validator = new Date();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid date array passed to App\\Form\\Validator\\Date validator');

        $validator->isValid([
            'day' => '1',
            'month' => '2',
        ]);
    }

    #[DataProvider('stringValidationProvider')]
    public function testStringValueMatchesParentValidator(string $value): void
    {
        $validator = new Date();
        $parentValidator = new LaminasDateValidator();

        $this->assertSame($parentValidator->isValid($value), $validator->isValid($value));
        $this->assertSame($parentValidator->getMessages(), $validator->getMessages());
    }

    public static function stringValidationProvider(): array
    {
        return [
            'valid string date' => ['2024-01-31'],
            'invalid string date' => ['not-a-date'],
        ];
    }
}
