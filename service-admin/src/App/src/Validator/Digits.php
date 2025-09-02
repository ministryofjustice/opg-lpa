<?php

namespace App\Validator;

use Laminas\Validator\Digits as ZendDigits;

/**
 * Class Digits
 * @package App\Validator
 *
 * @psalm-suppress InvalidExtendClass Will be resolved in LPA-3822
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
