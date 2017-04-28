<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Date extends SymfonyConstraints\Date
{
    use ValidatorPathTrait;

    const INVALID_FORMAT_ERROR = 1;
    const INVALID_DATE_ERROR = 2;

    protected static $errorNames = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::INVALID_DATE_ERROR => 'INVALID_DATE_ERROR',
    ];

    public $message = 'This value is not a valid date.';
}
