<?php

declare(strict_types=1);

namespace AppTest\Form\Validator;

use App\Form\Validator\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testValidPasswordReturnsTrue(): void
    {
        $validator = new Password();

        $this->assertTrue($validator->isValid('ValidPassword1'));
        $this->assertSame([], $validator->getMessages());
    }

    public function testMissingDigitReturnsExpectedError(): void
    {
        $validator = new Password();

        $this->assertFalse($validator->isValid('PasswordOnly'));
        $this->assertArrayHasKey(Password::MUST_INCLUDE_DIGIT, $validator->getMessages());
    }

    public function testMissingLowercaseReturnsExpectedError(): void
    {
        $validator = new Password();

        $this->assertFalse($validator->isValid('PASSWORD1'));
        $this->assertArrayHasKey(Password::MUST_INCLUDE_LOWER_CASE, $validator->getMessages());
    }

    public function testMissingUppercaseReturnsExpectedError(): void
    {
        $validator = new Password();

        $this->assertFalse($validator->isValid('password1'));
        $this->assertArrayHasKey(Password::MUST_INCLUDE_UPPER_CASE, $validator->getMessages());
    }

    public function testMultipleViolationsReturnAllErrors(): void
    {
        $validator = new Password();

        $this->assertFalse($validator->isValid('!!!!'));

        $this->assertSame([
            Password::MUST_INCLUDE_DIGIT => 'must-include-digit',
            Password::MUST_INCLUDE_LOWER_CASE => 'must-include-lower-case',
            Password::MUST_INCLUDE_UPPER_CASE => 'must-include-upper-case',
        ], $validator->getMessages());
    }
}
