<?php

namespace App\Validator;

use Laminas\Validator\Digits as ZendDigits;

/**
 * Class Digits
 * @package App\Validator
 */
class Digits extends ZendDigits
{
    /**
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::NOT_DIGITS   => 'digits-required',
        self::STRING_EMPTY => 'empty-string',
        self::INVALID      => 'invalid-type',
    ];
}
