<?php

namespace App\Validator;

use Laminas\Validator\AbstractValidator;

class EmailOrUserId extends AbstractValidator
{
    public const string INVALID = 'invalid';

    protected array $messageTemplates = [
        self::INVALID => 'Enter a valid email address or user ID',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        $value = trim($value);

        if (str_contains($value, '@')) {
            $emailValidator = new Email();
            return $emailValidator->isValid($value);
        }

        if (preg_match('/^[0-9a-zA-Z]+$/', $value)) {
            return true;
        }

        $this->error(self::INVALID);
        return false;
    }
}
