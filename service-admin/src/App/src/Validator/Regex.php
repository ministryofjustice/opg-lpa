<?php

namespace App\Validator;

use Laminas\Validator\Regex as ZendRegex;

/**
 * Class Regex
 * @package App\Validator
 *
 * @psalm-suppress InvalidExtendClass Will be resolved by LPA-3819
 */
class Regex extends ZendRegex
{
    /**
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::NOT_MATCH  => 'not-match',
    ];
}
