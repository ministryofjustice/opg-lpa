<?php

namespace App\Validator;

use Laminas\Validator\Regex as ZendRegex;

/**
 * Class Regex
 * @package App\Validator
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
