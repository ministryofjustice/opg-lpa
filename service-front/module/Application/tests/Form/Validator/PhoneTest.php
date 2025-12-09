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
            'UK Number' => ['+441234567890'],
            'US Number' => ['+12125550123'],
            'Minimum Length (7 digits)' => ['+1234567'],
            'Maximum Length (15 digits)' => ['+123456789012345'],
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
            'No Plus Sign' => ['441234567890'],
            'Starts with 0 after Plus' => ['+041234567890'],
            'Too Short' => ['+123456'],
            'Too Long' => ['+1234567890123456'],
            'Contains Hyphens' => ['+1-212-555-0123'],
            'Contains Letters' => ['+44123456789a'],
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
