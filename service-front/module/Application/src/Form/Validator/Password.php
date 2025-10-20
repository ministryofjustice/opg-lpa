<?php

namespace Application\Form\Validator;

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

    public function isValid($value)
    {
        $isValid = true;

        //  Check that a number has been provided
        $regExValidator = new Regex('/.*[0-9].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_DIGIT);
            $isValid = false;
        }

        //  Check that a lower case letter has been provided
        $regExValidator = new Regex('/.*[a-z].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_LOWER_CASE);
            $isValid = false;
        }

        //  Check that an upper case letter has been provided
        $regExValidator = new Regex('/.*[A-Z].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_UPPER_CASE);
            $isValid = false;
        }

        return $isValid;
    }
}
