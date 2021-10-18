<?php

namespace App\Validator;

use Laminas\Validator\NotEmpty as ZendNotEmpty;

/**
 * Class NotEmpty
 * @package App\Validator
 */
class NotEmpty extends ZendNotEmpty
{
    /**
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::IS_EMPTY => 'required',
        self::INVALID  => 'invalid-type',
    ];
}
