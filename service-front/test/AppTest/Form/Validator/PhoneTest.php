<?php

declare(strict_types=1);

namespace AppTest\Form\Validator;

use App\Form\Validator\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    #[DataProvider('validPhoneProvider')]
    public function testValidPhoneNumberReturnsTrue(string $phoneNumber): void
    {
        $validator = new Phone();

        $this->assertTrue($validator->isValid($phoneNumber));
        $this->assertSame([], $validator->getMessages());
    }

    public static function validPhoneProvider(): array
    {
        return [
            'number with spaces' => ['01234 567890'],
            'international format' => ['+44 (0)1234-567890'],
        ];
    }

    public function testInvalidPhoneNumberReturnsConfiguredErrorMessage(): void
    {
        $validator = new Phone();

        $this->assertFalse($validator->isValid('abc'));
        $this->assertSame([
            'notPhone' => 'Enter a valid phone number',
        ], $validator->getMessages());
    }
}
