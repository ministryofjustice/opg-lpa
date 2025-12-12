<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    private Phone $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Phone();
    }

    public static function validPhoneNumbersProvider(): array
    {
        return [
            'International Format Number' => ['+441234567890'],
            'Local Format Number' => ['01234567890'],
            'Minimum Length (6 digits)' => ['+123456'],
            'Maximum Length (20 digits)' => ['+12345678901234567890'],
            'Brackets' => ['+12(345)67890123456'],
            'Hyphens' => ['+12-345-67890123456'],
            'Spaces' => ['+12 345 67890123456'],
            // To keep regex easier to read the below would be valid along with 6 ) or ( characters
            'Accepted Characters' => ['------'],
        ];
    }

    #[DataProvider('validPhoneNumbersProvider')]
    public function testIsValidWithValidNumbers(string $number): void
    {
        $this->assertTrue($this->validator->isValid($number));
        $this->assertEmpty($this->validator->getMessages());
    }

    public static function invalidPhoneNumbersProvider(): array
    {
        return [
            'Too Short' => ['+12345'],
            'Too Long' => ['+123456789012345678910'],
            'Contains Letters' => ['+44123a56789'],
            'Contains Other Characters' => ['+44123!56789'],
            'Empty String' => [''],
        ];
    }

    #[DataProvider('invalidPhoneNumbersProvider')]
    public function testIsValidWithInvalidNumbers(string $number): void
    {
        $this->assertFalse($this->validator->isValid($number));

        $messages = $this->validator->getMessages();
        $this->assertArrayHasKey('notPhone', $messages);
        $this->assertEquals('Enter a valid phone number', $messages['notPhone']);
    }
}
