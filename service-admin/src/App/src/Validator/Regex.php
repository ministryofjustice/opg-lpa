<?php

namespace App\Validator;

use Zend\Validator\Regex as ZendRegex;

/**
 * Class Regex
 * @package App\Validator
 */
class Regex extends ZendRegex
{
    /**
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_MATCH  => 'not-match',
    ];
}
