<?php

declare(strict_types=1);

namespace AppTest\Validator;

use App\Validator\Email;
use Laminas\Validator\EmailAddress;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EmailTest extends TestCase
{
    public function testValidEmailPassesAndHasNoMessages(): void
    {
        $validator = new Email();
        $this->assertTrue($validator->isValid('user@example.gov.uk'));
        $this->assertSame([], $validator->getMessages());
    }

    public function testNonStringReturnsInvalidTypeMessage(): void
    {
        $validator = new Email();

        $this->assertFalse($validator->isValid(123));
        $messages = $validator->getMessages();

        $this->assertArrayHasKey(Email::INVALID_TYPE, $messages);
        $this->assertSame('invalid-email', $messages[Email::INVALID_TYPE]);
    }

    public function testInvalidFormatMapsAllInnerKeysToUnifiedMessage(): void
    {
        $validator = new Email();

        $this->assertFalse($validator->isValid('not-an-email'));
        $messages = $validator->getMessages();

        $this->assertNotEmpty($messages);
        foreach ($messages as $value) {
            $this->assertSame('invalid-email', $value);
        }
    }

    public function testFallsBackToInvalidWhenInnerHasNoMessages(): void
    {
        $validator = new Email();

        $this->replaceInnerValidator(
            $validator,
            new class extends EmailAddress {
                public function isValid($value): bool
                {
                    return false;
                }
                public function getMessages(): array
                {
                    return [];
                }
            }
        );

        $this->assertFalse($validator->isValid('anything@example.com'));
        $messages = $validator->getMessages();

        $this->assertArrayHasKey(Email::INVALID, $messages);
        $this->assertSame('invalid-email', $messages[Email::INVALID]);
    }

    public function testUnknownInnerCodeIsMappedToInvalid(): void
    {
        $validator = new Email();

        $this->replaceInnerValidator(
            $validator,
            new class extends EmailAddress {
                public function isValid($value): bool
                {
                    return false;
                }
                public function getMessages(): array
                {
                    return ['SOME_UNKNOWN' => 'whatever'];
                }
            }
        );

        $this->assertFalse($validator->isValid('user@example.com'));
        $messages = $validator->getMessages();

        $this->assertArrayHasKey(Email::INVALID, $messages);
        $this->assertSame('invalid-email', $messages[Email::INVALID]);
    }

    private function replaceInnerValidator(Email $validator, EmailAddress $stub): void
    {
        $ref = new ReflectionClass($validator);
        $prop = $ref->getProperty('emailValidator');
        $prop->setValue($validator, $stub);
    }
}
