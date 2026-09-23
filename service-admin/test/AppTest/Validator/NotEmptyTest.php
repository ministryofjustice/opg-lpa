<?php

declare(strict_types=1);

namespace AppTest\Validator;

use App\Validator\NotEmpty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NotEmptyTest extends TestCase
{
    public static function validProvider(): array
    {
        return [
            'simple string'          => ['foo'],
            'numeric string'         => ['123'],
            'zero integer allowed'   => [0],
            'zero string allowed'    => ['0'],
            'zero float allowed'     => [0.0],
            'array with something'   => [[1]],
        ];
    }
    public static function invalidProvider(): array
    {
        return [
            'empty string' => [''],
            'spaces only'  => ['   '],
            'null'         => [null],
            'empty array'  => [[]],
        ];
    }

    #[DataProvider('validProvider')]
    public function testItAcceptsExpectedValues(mixed $value): void
    {
        $validator = new NotEmpty();
        self::assertTrue($validator->isValid($value), 'Expected value to be considered NOT empty');
    }

    #[DataProvider('invalidProvider')]
    public function testItRejectsExpectedValues(mixed $value): void
    {
        $validator = new NotEmpty();
        self::assertFalse($validator->isValid($value), 'Expected value to be considered empty');
        self::assertNotEmpty($validator->getMessages());
    }
}
