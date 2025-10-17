<?php

declare(strict_types=1);

namespace AppTest\Validator;

use App\Validator\NotEmpty;
use PHPUnit\Framework\TestCase;

final class NotEmptyTest extends TestCase
{
    public function validProvider(): array
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
    public function invalidProvider(): array
    {
        return [
            'empty string' => [''],
            'spaces only'  => ['   '],
            'null'         => [null],
            'empty array'  => [[]],
        ];
    }

    /** @dataProvider validProvider */
    public function testItAcceptsExpectedValues(mixed $value): void
    {
        $validator = new NotEmpty();
        self::assertTrue($validator->isValid($value), 'Expected value to be considered NOT empty');
    }

    /** @dataProvider invalidProvider */
    public function testItRejectsExpectedValues(mixed $value): void
    {
        $validator = new NotEmpty();
        self::assertFalse($validator->isValid($value), 'Expected value to be considered empty');
        self::assertNotEmpty($validator->getMessages());
    }
}
