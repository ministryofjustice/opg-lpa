<?php

namespace Application\Form\Validator;

use Laminas\Validator\AbstractValidator;

class Phone extends AbstractValidator
{
    private const string NOT_PHONE = 'notPhone';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_PHONE => 'Enter a valid phone number',
    ];

    public function isValid($value)
    {
        $normalizedValue = preg_replace('/\s+/', '', (string) $value);
        $this->setValue($normalizedValue);

        $pattern = '/^\+[1-9]\d{6,14}$/';

        if (!preg_match($pattern, $value)) {
            $this->error(self::NOT_PHONE);
            return false;
        }

        return true;
    }
}
