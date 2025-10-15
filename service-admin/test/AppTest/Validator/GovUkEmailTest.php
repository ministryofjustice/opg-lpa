<?php

declare(strict_types=1);

namespace AppTest\Validator;

use App\Validator\GovUkEmail;
use PHPUnit\Framework\TestCase;
use Laminas\Validator\ValidatorInterface;

class GovUkEmailTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = new GovUkEmail();
    }

    public static function validEmails(): array
    {
        return [
            'base'          => ['user@justice.gov.uk'],
            'subdomain'     => ['first.last@sub.dept.gov.uk'],
            'plus-address'  => ['first.last+notify@justice.gov.uk'],
            'dashes'        => ['first-last@digital.justice.gov.uk'],
        ];
    }

    public static function invalidEmails(): array
    {
        return [
            'non-gov'       => ['user@example.com'],
            'missing-at'    => ['user.justice.gov.uk'],
            'no-local'      => ['@justice.gov.uk'],
        ];
    }

    /** @dataProvider validEmails */
    public function test_it_accepts_valid_govuk_emails(string $email): void
    {
        self::assertTrue($this->validator->isValid($email), 'Expected to be valid: ' . $email);
    }

    /** @dataProvider invalidEmails */
    public function test_it_rejects_invalid_or_non_govuk_emails(string $email): void
    {
        self::assertFalse($this->validator->isValid($email), 'Expected to be invalid: ' . $email);
        self::assertNotEmpty($this->validator->getMessages());
    }
}
