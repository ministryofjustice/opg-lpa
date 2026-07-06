<?php

declare(strict_types=1);

namespace AppTest\Form\Validator;

use App\Form\Validator\EmailAddress;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function testValidEmailAddressReturnsTrue(): void
    {
        $validator = new EmailAddress();

        $this->assertTrue($validator->isValid('user@example.com'));
        $this->assertSame([], $validator->getMessages());
    }

    public function testInvalidEmailAddressReturnsConfiguredErrorMessage(): void
    {
        $validator = new EmailAddress();

        $this->assertFalse($validator->isValid('not-an-email'));
        $this->assertSame([
            EmailAddress::INVALID_EMAIL => 'Enter a valid email address',
        ], $validator->getMessages());
    }
}
