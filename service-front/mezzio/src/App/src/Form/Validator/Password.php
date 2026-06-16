<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Regex;

class Password extends AbstractValidator
{
    public const MUST_INCLUDE_DIGIT      = 'mustIncludeDigit';
    public const MUST_INCLUDE_LOWER_CASE = 'mustIncludeLowerCase';
    public const MUST_INCLUDE_UPPER_CASE = 'mustIncludeUpperCase';

    protected $messageTemplates = [
        self::MUST_INCLUDE_DIGIT      => 'must-include-digit',
        self::MUST_INCLUDE_LOWER_CASE => 'must-include-lower-case',
        self::MUST_INCLUDE_UPPER_CASE => 'must-include-upper-case',
    ];

    public function isValid($value): bool
    {
        $isValid = true;

        $regExValidator = new Regex('/.*[0-9].*/');
        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_DIGIT);
            $isValid = false;
        }

        $regExValidator = new Regex('/.*[a-z].*/');
        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_LOWER_CASE);
            $isValid = false;
        }

        $regExValidator = new Regex('/.*[A-Z].*/');
        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_UPPER_CASE);
            $isValid = false;
        }

        return $isValid;
    }
}
